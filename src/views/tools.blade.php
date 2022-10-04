@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

@section('content')
    <div class="card">
        <div class="card-header">
            {{--Todo lang--}}
            <h3>@lang('Analyze')</h3>
        </div>
        <div class="card-body">
            @if (Auth()->user() !== null && isset($dataFile))
                <form id="toolForm">
                    @csrf

                    <div class="ids">
                        <div class="card crit-ident mb-3 tool-container ">
                            <div
                                class="card-header font-weight-bold">@lang('Select a file from your bookshelf and choose a tool')

                                {{--Todo popover text--}}
                                <a title="@lang('Info')" data-placement="top" data-toggle="popover"
                                   data-trigger="hover"
                                   data-content="@lang('Select a file and tool to create a frame with the Voyant technology. On the frame u can remove it or open it up in a new tab.')"><i
                                        class="fa fa-info-circle"
                                        style="color: grey"></i></a>
                            </div>
                            <div class="card-body file col-12">
                                <div class="rowsfile ">
                                    @foreach($selector as $selvalues)
                                        <div class="form-row tool-input container-input">
                                            <br>
                                            <div class="col-sm-9">
                                                {!! Form::label('file',__('Files'),['class' => 'col-form-label sr-only font-weight-bold']); !!}
                                                {!! Form::select('file', $dataFile, $selvalues['file_value'], ['class' => 'custom-select selectFile']) !!}
                                            </div>
                                            <div class="col-sm-3">
                                                {!! Form::label('tool', __('Tools'),['class' => 'col-form-label sr-only font-weight-bold']); !!}
                                                {!! Form::select('tool', $tools, $selvalues['tool_value'], ['class' => 'custom-select selectTool']) !!}
                                            </div>
                                            <div class="w-100 d-block d-sm-none mb-2"></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="form-row inputButton">
                            <div class="col-12">
                                <a target="_blank" style="float: right">
                                    {{--Todo lang--}}
                                    {{Form::button(__('Analyze'),['class'=>'btn btn-primary add-frame'])}}
                                </a>
                            </div>
                        </div>
                        <br>
                        <div class="toolFrame">
                        </div>
                    </div>
                </form>
            @else
                <div class="card">
                    {{--<h4 class="card-header">@lang('Tools')</h4>--}}
                    {{--<div class="card-body" style="justify-content: center">--}}
                    <div class="bookshelfContent">
                        <div class="search-result-citation py-2 home">
                            <h3>@lang('No Files: Bookshelf is empty')</h3>
                        </div>
                    </div>
                    {{--</div>--}}
                </div>
            @endif
        </div>
    </div>
@endsection

