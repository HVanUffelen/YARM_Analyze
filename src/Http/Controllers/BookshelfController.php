<?php

namespace Yarm\Bookshelf\Http\Controllers;

use App\Http\Controllers\BookshelfBaseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PaginationController;
use App\Http\Controllers\SQLRelated\JoinController;
use App\Http\Controllers\SQLRelated\SortController;
use App\Http\Controllers\ValidationController;
use App\Models\File;
use App\Models\Identifier;
use App\Models\Role;
use App\Models\Roles4user;
use App\Models\Shelf_book;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Redirect;
use Response;

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
        return view('dlbt.bookshelf.bookshelf', $data);
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
        if (BookshelfBaseController::countBookshelfItems() === 0) {
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
            if (Auth()->user()) BookshelfBaseController::deleteOldBooksFromWebsiteUsers();
        } catch (\Throwable $e) {
            return response()->json(['danger' => 'Unable to delete entries of website-users older than 2 days!']);
        }
        //build query and data
        $query = BookshelfBaseController::makeQuery4bookShelf(BookshelfBaseController::makeIdsArray4bookShelf(), $request, $blade);
        $data['rows'] = $query->paginate($paginationValue);
        $data = BookshelfBaseController::makeDataRows($data);

        //Create link data for view
        $data = BookshelfBaseController::createLinkDataForView($data);
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
            return view('dlbt.bookshelf.inc.' . $template, $data);
        }
    }
}
