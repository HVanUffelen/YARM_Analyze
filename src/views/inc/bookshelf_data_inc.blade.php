@php
//dd ($records)
@endphp
@if (isset($records))
    @foreach ($records as $record)
        <div class="card bookshelf mt-3">
            <div class="card-header">
                <h5>
                    {!! \App\Http\Controllers\ExportController::reformatBladeExport(view('dlbt.styles.format_as_' . App\Models\Style::getNameStyle(), array('ref'=> $record['ref']))->render()) !!}
                </h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col">@lang('File Info')</th>
                        <th></th>
                        <th scope="col">@lang('Convert to')</th>
                    </tr>
                    </thead>

                   <tbody>
                        @foreach ($record['files'] as $file)
                            <tr>
                                <td><a title="@lang('Remove file')" href="javascript:void(0)" id="delete-bookshelf-id"
                                       data-id="{{ $file['id']}}"
                                       class="fa fa-trash  deleteBookshelfButton"
                                       style="color:red"></a>
                                </td>
                                <td scope="row">
                                    {!! '<span class="font-weight-bold">' . strtoupper($file['type'])  .' -</span>'!!}
                                    @if ($file['downloaded'] == 'true')
                                        {!! 'On external Server<br/>' !!}
                                    @else
                                        {!! 'On Server DLBT<br/>' !!}
                                    @endif

                                    @php(($fileName = (strlen($file['fileName']) > 40) ? substr($file['fileName'], 0, 40) . '...' : $file['fileName']))
                                    @php(($comment = (strlen($file['comment']) > 40) ? substr($file['comment'], 0, 40) . '...' : $file['comment']))

                                    {!! $comment!=""?'(' . $comment . ')<br/>':''!!}
                                    {!! $fileName!=""?'[' . $fileName . ']':''!!}
                                    @php($fileID = (($file['file_id'])? $file['file_id']: $file['identifier_id']))
                                    @php($type = (($file['file_id'])? 'file': 'identifier'))
                                </td>
                                <td><a title="@lang('Download original file')"
                                       href="/dlbt/downloadFile/?action=original&format=original&name={{ $file['fileName']}}&fileId={{$fileID}}"><i
                                            class="fa fa-download mr-1"
                                            style="color:seagreen"></i></a></td>
                                <td>
                                    @foreach ($file['formats'] as $format)
                                        @php(($filePath = ($file['downloaded'] != 'false') ? $file['pathAndName'] : $file['fileName']))
                                        <a title="@lang('Download as') {{ strtoupper($format) }}"
                                           href="/dlbt/downloadFileAs/?convertible={{$file['convertible']}}&fileFormat={{$file['type']}}&convFormat={{$format}}&file={{$filePath}}&id={{$file['id']}}&type={{$type}}&fileId={{$fileID}}">
                                            <img style="display: inline-block; width: 15px" src="{{asset('Images/' . $format . '.png')}}">
                                        </a>
                                    @endforeach
                                </td>
                            </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
@if (isset($records))
    <div class="pagination">
        {{ $rows->links() }}
    </div>
@endif


