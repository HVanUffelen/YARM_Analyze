<?php

namespace Yarm\Analyze\Http\Controllers;

use Yarm\Bookshelf\Http\Controllers\BookshelfController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class VoyantController extends Controller
{
    /**
     * Load test frame tool 'cirrus' with voyant, 'not used anymore'
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showCirrus()
    {
        return view('analyze::voyantTest');
    }

    /**
     * Load test frame for dlbt website
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showTestFrame()
    {
        return view('analyze::test.test_frame');
    }

    /**
     * Loads view with dropdowndata
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function toolsForm(Request $request)
    {

        //check if user is verified
        if (auth()->user() && !auth()->user()->hasVerifiedEmail()) return redirect('email/verify');

        //Hide layout when user is Typo3DLBT
        ValidationController::checkIfUserIsTypo3DLBT($request);

        //Dropdown data for the Voyant tools
        $data['tools'] = [
            'Bubblelines' => trans('Bubblelines'),
            'Cirrus' => trans('Cirrus', [], Session::get('userLanguage')),
            'Correlations' => trans('Correlations', [], Session::get('userLanguage')),
            'Documents' => trans('Documents', [], Session::get('userLanguage')),
            'DreamScape' => trans('DreamScape', [], Session::get('userLanguage')),
            'Links' => trans('Links', [], Session::get('userLanguage')),
            'Loom' => trans('Loom', [], Session::get('userLanguage')),
            'Mandala' => trans('Mandala', [], Session::get('userLanguage')),
            'MicroSearch' => trans('MicroSearch', [], Session::get('userLanguage')),
            'Phrases' => trans('Phrases', [], Session::get('userLanguage')),
            'Reader' => trans('Reader', [], Session::get('userLanguage')),
            'ScatterPlot' => trans('ScatterPlot', [], Session::get('userLanguage')),
            'StreamGraph' => trans('StreamGraph', [], Session::get('userLanguage')),
            'Summary' => trans('Summary', [], Session::get('userLanguage')),
            'TermsRadio' => trans('TermsRadio', [], Session::get('userLanguage')),
            'Topics' => trans('Topics', [], Session::get('userLanguage')),
            'Trends' => trans('Trends', [], Session::get('userLanguage')),
            'Veliza' => trans('Veliza', [], Session::get('userLanguage')),
            'WordTree' => trans('WordTree', [], Session::get('userLanguage'))
        ];

        //Standard selected value
        $data['selector'] = [
            '0' => [
                'file_value' => '',
                'tool_value' => 'Cirrus',
            ],
        ];

        //Get all bookshelfdata form active user
        //Todo decide how many paginationValue???
        $bookShelfData = BookshelfController::getBookshelfData('Voyant', 1000);

        //use ref to create dropdown data for bookshelf files
        if (isset($bookShelfData['records'])) {
            foreach ($bookShelfData['records'] as $record) {
                $prepRef = $record['ref']->prepareDataSet();
                foreach ($record['files'] as $file) {
                    $out = [
                        $prepRef['author'],
                        explode(".", $prepRef['title'])[0],
                        $prepRef['volume'],
                        $prepRef['year'],
                        "(" . strtoupper(substr($file['fileName'], strlen($file['fileName']) - 3, 3)) . ")"
                    ];

                    //Set fileKey to get file in tools.js
                    $fileKey = $file['fileName'];

                    //Add path to dir downloaded or unzipped to key
                    if (isset($file['pathAndName']))
                        if ($file['identifier_id'] != 0)
                            $fileKey = $file['pathAndName'] . '/';

                    $data['dataFile'][$fileKey] = implode(' ', $out);
                }
            }
        }

        return view('analyze::tools', $data);
    }
}
