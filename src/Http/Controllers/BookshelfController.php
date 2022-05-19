<?php

namespace Yarm\Bookshelf\Http\Controllers;

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PaginationController;
use App\Http\Controllers\ReadableParserController;
use App\Http\Controllers\SQLRelated\JoinController;
use App\Http\Controllers\SQLRelated\SortController;
use App\Http\Controllers\ValidationController;
use App\Models\File;
use App\Models\Identifier;
use App\Models\Role;
use App\Models\Roles4user;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Redirect;
use Response;
use App\Models\Shelf_book;

class BookshelfController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Delete item from bookshelf
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function destroy(Request $request)
    {
        $id = $request['Id'];
        $book = Shelf_book::find($id);

        if ($book) {
            try {
                FileController::deleteDownloadedZippedShelfBooksFromServer($book);
                $book->delete();
                return response()->json(['success' => __('Record deleted successfully!')]);
            } catch (\Throwable $e) {
                return response()->json(['danger' => __('Unable to delete files from Server!')]);
            }
        } else {
            //only return response if user deletes from Bookshelf
            return response()->json(['danger' => 'Unable to delete file (File not found)!']);
        }
    }

    /** Store added file to the bookshelf
     * Recieves data from script tools.js through AJAX call
     * @param Request $request
     * @return string
     */

    public function store(Request $request)
    {
        //Assign values to data
        $bookshelf = new Shelf_book;
        $record = [];
        $record['session_id'] = Session::getID();
        $record['user_id'] = Auth::user()->id;
        $record['ref_id'] = (isset($request->refId)) ? (int)$request->refId : 0;

        //Determine to use file or identifier id to check on duplicates
        //return is no id!!
        $record['file_id'] = (isset($request->Id)) ? (int)$request->Id : 0;
        $record['identifier_id'] = (isset($request->identifierId)) ? (int)$request->identifierId : 0;
        if ($record['file_id'] == 0 && $record['identifier_id'] == 0)
            return Response::json(array('success' => false, 'errors' => __('Unknown error.')), 400);

        //check if book is already unzipped or already on shelf, else add it
        try {
            $record = self::checkIfAlreadyUnzipped($record, $request);
            if (self::checkIfBookAlreadyOnShelf($record) == 0) {
                $bookshelf->updateOrCreate($record);
                return Response::json(array('success' => true, 'errors' => __('Book in Store!')), 200);
            } else {
                return Response::json(array('success' => false, 'errors' => __('Book already on the shelf!')), 400);
            }
        } catch (\Throwable $e) {
            return response()->json(['danger' => __('Unable to save data!')]);
        }
    }

    /** Check if Book to store is already unzipped
     * @param $record
     * @param $request
     * @return mixed //$record
     */
    private static function checkIfAlreadyUnzipped($record, $request)
    {
        //Check if zip already unzipped!
        if (strpos($request['fileName'], '.zip') !== false) {
            $name = substr(basename($request['fileName']), 0, strpos(basename($request['fileName']), "."));
            $fileToConvert = 'DLBTUploads/unzipped/' . $name . '\\' . 'tei.xml';
            //check if file exist
            if (storage::exists($fileToConvert)) {
                //check if file is readable
                if (strpos(storage::get($fileToConvert), 'teiHeader') !== false) {
                    $record['readable'] = true;
                    $record['checked'] = true;
                }
                $record['pathAndName'] = $name . '/' . 'tei.xml';
                $record['checked'] = true;
                $record['unzipped'] = true;
                $record['type'] = 'zip';
            }
        }
        return $record;
    }


    /** Check if book is already on shelf
     * @param $record
     * @return mixed //Book found  (1 or 0)
     */
    private static function checkIfBookAlreadyOnShelf($record)
    {
        $book = Shelf_book::where([
            ['identifier_id', '=', $record['identifier_id']],
            ['file_id', '=', $record['file_id']],
            ['user_id', '=', Auth::user()->id]
        ]);
        if (Auth()->user() && Auth()->user()->roles()->pluck('role')[0] == 'Website') {
            $book->where('session_id', '=', session::getId());
        }
        return $book->count();
    }

    /**
     * Get main bookshelf view
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function bookshelfForm(Request $request)
    {
        //check if user is verified
        if (auth()->user() && !auth()->user()->hasVerifiedEmail()) return redirect('email/verify');

        //Hide layout when user is Typo3DLBT
        ValidationController::checkIfUserIsTypo3DLBT($request);

        $paginationValue = PaginationController::getPaginationItemCount();

        //Get bookshelf data and return bookshelf
        $data = self::getBookshelfData('Bookshelf', $paginationValue, $request);
        return view('bookshelf::bookshelf', $data);
    }

    /**
     * Get data for bookshelf
     * @param $blade //Sidebar or main bookshelf
     * @param int $paginationValue
     * @param null $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public static function getBookshelfData($blade, $paginationValue = 50, $request = null)
    {
        //check first if there are books in shelf
        if (self::countBookshelfItems() === 0) {
            $data['message'] = trans(ucfirst('Bookshelf is empty'), [], Session::get('userLanguage'));
            return $data;
        }

        //initialize $data
        $data = [
            "message" => "",
            "rows" => null,
            "records" => null,
            "types" => [
                'epub' => 'Epub',
                'html' => 'Html',
                'odt' => 'Odt',
                'pdf' => 'Pdf',
                'word' => 'Word',
            ]
        ];

        //delete all entries of website-users older than 2 days
        try {
            if (Auth()->user()) self::deleteOldBooksFromWebsiteUsers();
        } catch (\Throwable $e) {
            return response()->json(['danger' => 'Unable to delete entries of website-users older than 2 days!']);
        }
        //build query and data
        $query = self::makeQuery4bookShelf(self::makeIdsArray4bookShelf(), $request, $blade);
        $data['rows'] = $query->paginate($paginationValue);
        $data = self::makeDataRows($data);

        //Create link data for view
        $data = self::createLinkDataForView($data);
        return $data;
    }








    /**
     * Send data to sidebar view or main bookshelf inc
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function bookshelfFetch(Request $request)
    {
        //check if user is verified
        if (auth()->user() && !auth()->user()->hasVerifiedEmail()) return redirect('email/verify');

        //set blade and template according to sidebar
        $blade = ($request['sidebar'] == true) ? 'Sidebar' : 'Bookshelf';
        $template = ($request['sidebar'] == true) ? 'bookshelf_data_sidebar_inc' : 'bookshelf_data_inc';
        $paginationValue = 5;// todo this should probably be set via the user preferences

       if ($request->ajax()) {
            $data = self::getBookshelfData($blade, $paginationValue, $request);
            return view('bookshelf::inc.' . $template, $data);
        }
    }

    /** get Bookshelf count
     * @return int
     */
    private static function countBookshelfItems()
    {
        return (Auth()->user() && Auth()->user()->roles()->pluck('role')[0] == 'Website') ?
            Shelf_book::where('user_id', '=', Auth()->user()->id)
                ->where('session_id', '=', Session::getID())
                ->count()
            : Auth()->user()->booksInShelf()->count();
    }


    /**
     * Delete old stored books from WebsSiteUsers
     */
    private static function deleteOldBooksFromWebsiteUsers()
    {

        $role = Roles4user::where('role', 'Website')->first();
        $websiteUsers = $role->belongsToMany(User::class, 'roles4user_user')->get();
        if ($websiteUsers) {
            foreach ($websiteUsers as $websiteUser) {
                $booksIds = Shelf_book::where('created_at', '<=', now()->subDays(2)->toDateTimeString())
                    ->where('user_id', $websiteUser->id)
                    ->pluck('id')
                    ->toArray();
                Shelf_book::whereIn('id', $booksIds)->delete();
            }
        }
    }

    /**
     * Make data for sidebar, "filter results by", pagination
     * @param $idsArray
     * @param $request
     * @param $blade //Sidebar or main bookshelf
     * @return mixed $query
     */
    private static function makeQuery4bookShelf($idsArray, $request, $blade)
    {
        $roles = Role::roles();

        //Check input from typed in "filter results by"  $request->query

        if (isset($request)) $q = str_replace(" ", "%", $request->get('query'));

        if (!isset($request['sort1'])) $request = SortController::makeSortorderArray($request);
        $query =  JoinController::buildJoints($roles, $request)->groupBy('refs.id');

        //Check if blade is sidebar and set pagination, else make data for main bookshelf form
        $query = self::buildQueryBasedOnParams($query, $blade, $idsArray, $q ?? '');

        $query = SortController::addSortCriteria($query, $request, 'ListView', '');
        return $query;
    }

    /** Check if blade is sidebar and set pagination, else make data for main bookshelf form
     * @param $query
     * @param $blade
     * @param $idsArray
     * @param $q
     * @return mixed $query
     */
    private static function buildQueryBasedOnParams($query, $blade, $idsArray, $q)
    {
        if ($blade == 'Sidebar') {
            $booksIds = Shelf_book::select('ref_id')
                ->where('user_id', '=', Auth()->user()->id)
                ->orderBy('id', 'desc')->take(5)->get();
            $query->whereIn('refs.id', $booksIds);;
        } else {
            $query->whereIn('refs.id', $idsArray);
            if (isset($q)) {
                $arrayWheres = explode(',', config('yarm.filterresults_by', "n2.name,n11.name,refs.title,refs.container,refs.year,refs.place,refs.publisher"));
                $countWheres = count($arrayWheres);
                $query->where(function ($query) use ($q, $arrayWheres, $countWheres) {
                    $query->where($arrayWheres[0], 'like', '%' . $q . '%');
                    for ($i = 1; $i < $countWheres; $i++) {
                        $query->orWhere($arrayWheres[$i], 'like', '%' . $q . '%');
                    };
                });
            }
        }
        return $query;
    }

    /** make array of ids to look for data in refs
     * @return mixed
     */
    private static function makeIdsArray4bookShelf()
    {
        return (Auth()->user() && auth()->user()->roles()->first()->role == 'Website')
            ? Auth()->user()->booksInShelfdistinctRefIds4WebsiteUsers(Session::getId())->pluck('ref_id')->toArray()
            : Auth()->user()->booksInShelfdistinctRefIds()->pluck('ref_id')->toArray();
    }

    private static function makeDataRows($data)
    {
        $data['records'] = [];
        foreach ($data['rows'] as $row) {
            if (!array_key_exists($row['id'], $data['records'])) $data['records'][$row['id']] = ["ref" => $row, "files" => []];
        }
        return $data;
    }


    /** get Bookshelf count
     * @param $data
     * @return
     */
    private static function createLinkDataForView($data)
    {
        //Create link data for view
        $allFiles = self::getBookshelfItems();
        foreach ($allFiles as $file) {
            if (array_key_exists($file['ref_id'], $data['records'])) {
                $link = self::createLinkData($file);
                if ($link != false)
                    $data['records'][$file['ref_id']]['files'][] = self::createLinkData($file);
            }
        }
        return $data;
    }

    /** get files in Bookshelf
     * @return list
     */
    private static function getBookshelfItems()
    {
        return (Auth()->user() && Auth()->user()->roles()->pluck('role')[0] == 'Website') ?
            Shelf_book::where('user_id', '=', Auth()->user()->id)
                ->where('session_id', '=', Session::getID())
                ->get()
            : Auth()->user()->booksInShelf()->get();
    }

    /**
     * @param $file
     * @return array //Link
     */
    private static function createLinkData($file)
    {
        $link = [
            'file_id' => $file['file_id'],
            'identifier_id' => $file['identifier_id'],
            'id' => $file['id'],
            'ref_id' => $file['ref_id']
        ];
        $file_id_Present = (isset($file['file_id']) && $file['file_id'] != 0);

        $fileData = $file_id_Present ? File::where('id', '=', $file['file_id'])->get()
            : Identifier::where('id', '=', $file['identifier_id'])->get();
        //check if file still exists!
        if (isset($fileData[0])) {
            $link['fileName'] = $fileData[0][$file_id_Present ? 'name' : 'value'];
            $link['comment'] = $fileData[0]['comment'];
            list($convertible, $type, $downloaded, $pathAndName)
                = self::convertible($fileData[0]['comment'], $file['id'], $fileData[0][$file_id_Present ? 'name' : 'value']);
            $link['convertible'] = $convertible;
            $link['downloaded'] = $downloaded;
            $link['unzipped'] = $file['unzipped'];
            $link['pathAndName'] = ($file['unzipped'] == 'true') || ($file['downloaded'] == 'true')
                ? ((!$file_id_Present && $file['downloaded'] == 'true') ? '/downloaded/' . $pathAndName : '/unzipped/' . $pathAndName) : '';
            $link['type'] = $type;
            $link['formats'] = self::makeArrayFormats($link);

            return $link;
        } else
            return false;

    }
    /**
     * Formats for convert and download
     * @param $link //Includes data (type, if convertible,..)
     * @return array
     */
    private static function makeArrayFormats($link)
    {
        //No conversion to tei, xml, txt and zip
        //Todo epub is broken
        //$mtypes = ['pdf', 'word', 'html', 'epub'];
        $mtypes = ['pdf', 'word', 'html'];
        $linkType = (isset($link['type'])) ? $link['type'] : 'zip';

        $noConversion = ($linkType == 'pdf' && $link['convertible'] == true)
            ? ['word', 'epub'] : ['pdf', 'word', 'epub'];

        foreach ($mtypes as $format) {
            //Don't show icon for file that can be downloaded and  types that are excluded
            if (($format != $linkType) && (!is_numeric(array_search($linkType, $noConversion)))) $arrayFormats[] = $format;
        }
        return $arrayFormats ?? [];
    }

    /**
     * Check if file is convertible
     * @param $comment
     * @param $id
     * @param $name
     * @return array
     */
    private static function convertible($comment, $id, $name)
    {
        $typesForDownload = array('pdf', 'word', 'html', 'txt', 'xml', 'tei', 'zip');
        $convertible = false;
        $book = Shelf_book::find($id);

        if ((strpos(strtolower($comment), 'tiff') === false)
            && is_numeric(strpos(strtolower($name), '.pdf'))) {
            if (self::checkIfPDFIsDownloadableAndReadable($name, $book, 'pdf')) {
                $type = 'pdf';
                $book->type = $type;
                $book->checked = 'true';
                $book->save();
                $convertible = true;
            }
        } else if (strpos(strtolower($name), '.xml') !== false) {
            $forXML = self::forXML($name, $book, $convertible);
            $book = $forXML[0];
            $convertible = $forXML[1];
            $type = $forXML[2];

        } else if (strpos(strtolower($name), 'http') !== false) {
            $forHTTP = self::forHTTP($name, $book, $typesForDownload, $convertible, $type ?? '');
            $book = $forHTTP[0];
            $convertible = $forHTTP[1];
            $type = $forHTTP[2];
        }
        if (!isset($type)) {
            $fileInfo = pathinfo($name);
            $type = (isset($fileInfo['extension'])) ? $fileInfo['extension'] : 'Unknown type';
            $book->type = $type;
            $book->save();
        }
        return array($convertible, $book->type, $book->downloaded, $book->pathAndName);
    }

    //If file is XML
    private static function forXML($name, $book, $convertible)
    {
        $type = 'xml';
        //loadFile and check if file is TEI
        if ($book->checked == 'false' && storage::exists('DLBTUploads/' . $name)) {
            $fileContent = storage::get('DLBTUploads/' . $name);
            if (strpos($fileContent, 'TEI version') !== false || strpos($fileContent, 'teiHeader') !== false) {
                $book->readable = 'true';
                $book->checked = 'true';
                $type = 'TEI-xml';
                $book->type = $type;
                $book->save();
                $convertible = true;
            }
        } else if (strpos(strtolower($name), '.txt') !== false) {
            //Todo check if file is readable ??
            $book->type = 'txt';
            $book->save();
            $convertible = true;
        }
        return [$book, $convertible, $type];
    }

    //If file is HTTP link
    private static function forHTTP($name, $book, $typesForDownload, $convertible, $type)
    {
        if (self::checkUrlExists($name) !== false) {
            if (strpos(strtolower($name), 'phaidra') !== false) {
                $url = DownloadController::tryToConvertURLToPhaidraDownloadLink($name);
                $type = self::checkTypeOfDownloadlink($url);
                $book->type = self::makeTypeFromMimeType($type, $typesForDownload);

                $book->save();
                if ($type) {
                    foreach ($typesForDownload as $tFDownload) {
                        if (strpos(strtolower($type), $tFDownload) !== false)
                            if (self::checkIfPDFIsDownloadableAndReadable($url, $book, $tFDownload)) {
                                $convertible = true;
                            }
                        $book->checked = 'true';
                        $book->save();
                    }
                }
            } else {
                $type = self::checkTypeOfDownloadlink($name);
                $book->type = self::makeTypeFromMimeType($type, $typesForDownload);
                $book->save();
                if ($type) {
                    foreach ($typesForDownload as $tFDownload) {
                        if (strpos(strtolower($type), $tFDownload) !== false) {
                            $book->save();
                            $convertible = true;
                        }
                    }
                }
            }
        }
        return [$book, $convertible, $type];
    }
    /**
     * Checks if pdf is downloadable and readable
     * @param $file
     * @param $book
     * @param $type
     * @return bool
     */
    private static function checkIfPDFIsDownloadableAndReadable($file, $book, $type)
    {
        if (strpos(strtolower($file), 'http') !== false) {
            //File is downloaded from Phaidra
            $file_name = (basename($file) == 'download' && strpos($file, 'phaidra') !== false)
                ? $book->identifier_id . '_' . $book->ref_id . '.' . $type : basename($file);

            $name = substr($file_name, 0, strpos($file_name, "."));
            $path_name = 'DLBTUploads/downloaded/' . $name . '/' . $file_name;

            self::ifFileDontExistAddIt($file, $path_name);

            if (storage::exists($path_name) === false || ($book->pathAndName == null)) $book->pathAndName = $name . '/' . $file_name;
            $book->downloaded = (storage::exists($path_name) === false || ($book->pathAndName == null)) ? 'true' : 'false';
            $book->save();

            if (storage::exists($path_name) === false && $book->readable != 'true')
                $path_name = storage_path() . '/app/DLBTUploads/downloaded/' . $name . '/' . $file_name;
            $book->readable = (storage::exists($path_name) === false && $book->readable != 'true')
                ? ((ReadableParserController::checkIfPDFIsReadable($path_name)) ? 'true' : 'false') : 'false';

            $book->save();
            return (storage::exists($path_name) === false && $book->readable != 'true')
                ? ((ReadableParserController::checkIfPDFIsReadable($path_name)) ? true : false) : true;
        }
        //check if file exists on server
        return (strpos(strtolower($file), 'http') == false)
            ? self::fileExistInUploads($book, $file) : false;
    }



    /**
     * @param $url
     * @return bool|mixed
     */
    public static function checkTypeOfDownloadlink($url)
    {
        $file_headers = @get_headers($url);
        $Types = array('Content-Type: application/pdf', 'Content-Type: txt/xml', 'Content-Type: application/msword', 'Content-Type: text/html', 'Content-Type: txt/plain');
        $Type = false;
        foreach ($Types as $application) {
            if (array_search($application, $file_headers) !== false) {
                $Type = $application;
                return $Type;
            }
        }
    }

    private static function ifFileDontExistAddIt($file, $path_name)
    {
        $downloadedFile = (storage::exists($path_name) === false) ? file_get_contents($file) : '';

        if (storage::exists($path_name) === false && $downloadedFile !== '')
            Storage::disk('local')->put($path_name, $downloadedFile);
    }

    private static function fileExistInUploads($book, $file)
    {
        $file_name = basename($file);
        $path_name = '/DLBTUploads/' . $file_name;
        if (storage::exists($path_name) == true) {
            if ($book->readable != 'true') {
                $book->readable = (ReadableParserController::checkIfPDFIsReadable(storage_path() . '/app/DLBTUploads/' . $file_name)) ? 'true' : 'false';
                $book->save();
                return (bool)ReadableParserController::checkIfPDFIsReadable(storage_path() . '/app/DLBTUploads/' . $file_name);
            }
        }
        return true;
    }


    /**
     * Check if url exists
     * @param $url
     * @return bool
     */
    private static function checkUrlExists($url)
    {
        $file_headers = @get_headers($url);
        //If the header contains one of these codes, it doesnt exist
        return in_array($file_headers[0], ['404', '403', '500']) ? false : true;
    }

    /**
     * Check for TEI
     * @param $mimeType
     * @param $typesForDownload
     * @return mixed
     */
    private static function makeTypeFromMimeType($mimeType, $typesForDownload)
    {
        return ($mimeType == 'TEI-xml') ? $mimeType : (self::getMimeType($mimeType, $typesForDownload));
    }

    private static function getMimeType($mimeType, $typesForDownload)
    {
        foreach ($typesForDownload as $mType) {
            if (strpos($mimeType, $mType) !== false) $type = $mType;
        }
        return $type ?? null;
    }

}
