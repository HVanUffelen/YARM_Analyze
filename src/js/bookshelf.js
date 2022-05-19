//From bookshelf package
;(function ($) {
        $(document).ready(function () {
            /* Setup for ajax*/
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('conFtent')
                }
            });

            //ADD to bookshelf
            $(document).on('click', '#add-to-bookshelf', function () {
                var file = $(this).data();
                $('#modalTitle').html(YARM.bookshelf_title);
                $('#fileID').val(file['id']);
                $('#fileName').val(file['name']);
                $('#refID').val(file['refid']);
                $('#identifierID').val(file['identifierid']);

                //Make errors empty
                $('.error_bookshelf').html('');
                $('.error_danger').removeClass('alert alert-danger');
                $('#btn-add-to-bookshelf').html('Add');

                $('#bookshelfModalContent').html('<i class="fa fa-book" style="color: seagreen"></i>' + YARM.bookshelf_add_message1 + '<span class="font-weight-bold"> ' + file['name'] + ' </span>' + YARM.bookshelf_add_message2);
                $('#bookShelfConfirmationModal').modal('show');
            });

            $(document).on('click', '#close-bookshelf-modal', function () {
                $('#bookShelfConfirmationModal').modal('hide');
            });

            if ($("#bookshelfForm").length > 0) {
                $("#bookshelfForm").validate({
                    submitHandler: function (form) {
                        var form = $(form);
                        var refID = form.find('input#refID').val();
                        var fileName = form.find('input#fileName').val();
                        var fileID = null;
                        var identifierID = null;

                        if (form.find('input#fileID').val() !== undefined)
                            fileID = form.find('input#fileID').val();

                        if (form.find('input#identifierID').val() !== undefined)
                            identifierID = form.find('input#identifierID').val();

                        var url = "/dlbt/bookshelf?Id=" + fileID + "&refId=" + refID + "&identifierId=" + identifierID + "&fileName=" + fileName;

                        $('#btn-add-to-bookshelf').html('Sending ...');

                        $.ajax({
                            type: "POST",
                            url: url,
                            success: function (data) {
                                fetch_data();
                                $('#bookshelfForm').trigger("reset");
                                $('#bookShelfConfirmationModal').modal('hide');
                                $('#btn-add-to-bookshelf').html('Add');
                                var bookshelfCounter = parseInt($('#bookshelf-counter').html());
                                bookshelfCounter++;
                                $('#bookshelf-counter').html(bookshelfCounter);
                            },
                            error: function (data) {
                                //Set error
                                $('.error_bookshelf').append('<span class="font-weight-bold">' + data.responseJSON.errors + '</span><br>');

                                //Add alter-danger class and change btn text
                                $('.error_danger').addClass('alert alert-danger');
                            }
                        });
                    }
                })
            }

            //DELETE BookshelfID
            $(document).on('click', '#delete-bookshelf-id', function () {
                var bookshelfId = $(this).data('id');
                $('#bookShelfModalDelete').html(YARM.delete_this + bookshelfId + "?");
                $('#btn-delete-bookshelf').val("btn-delete");
                $('#btn-delete-bookshelf').data('id', bookshelfId);
                $('#modalDeleteBookshelf').modal('show');
            });

            $(document).on('click', '#btn-delete-bookshelf', function () {
                var id = $(this).data('id');
                $.ajax({
                    type: 'GET',
                    url: '/dlbt/bookshelfDelete?Id=' + id,
                    success: function (data) {
                        $('#modalDeleteBookshelf').modal('hide');
                        window.location.reload();
                    },
                    error: function (data) {
                    }
                });
            });

            $(document).on('click', '#btn-delete-dismiss-bookshelf', function () {
                $('#modalDeleteBookshelf').modal('hide');
            });

            function fetch_data() {
                $.ajax({
                    url: '/dlbt/bookshelf/bookshelfFetch?sidebar=true',
                    success: function (data) {
                        $('.bookshelf-sidebar-content').html('');
                        $('.bookshelf-sidebar-content').html(data);
                    },
                })
            }
        });
    }
    (jQuery)
);
