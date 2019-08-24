(function(_, $) {
    var bulkEditMode = false;

    $.ceEvent('on', 'ce.commoninit', function (context) {
        var $elems = $('.cm-object-categories-add', context),
            category_ids = [],
            categories_map = {};

        if ($elems.length) {
            $.each($elems, function () {
                var value = $(this).val();

                if (!value) {
                    return;
                }

                if (!Array.isArray(value)) {
                    value = [value];
                }

                if ($(this).data('caBulkEditMode')) {
                    categories_map = $(this).data('caBulkEditModeCategoryIds');   
                    bulkEditMode = true;
                }

                category_ids = category_ids.concat(value);
            });

            if (category_ids.length) {
                fn_actualize_selected_categories_list_data(category_ids, $elems, categories_map);
            }
        }
    });

    $.ceEvent('on', 'ce.change_select_list', function (object, $container) {
        if ($container.hasClass('cm-object-categories-add') && object.data) {
            object.context = object.data.content;
        }
    });

    $.ceEvent('on', 'ce.select_template_selection', function (object, list_elm, $container) {
        if ($container.hasClass('cm-object-categories-add') && object.data) {
            if (object.data.disabled) {
                $(list_elm).addClass('select2-drag--disabled');
            }

            if (object.data.disabled || (object.data.remover === false) || object.bulkEditMode) {
                $(list_elm).find('.select2-selection__choice__remove').remove();
            }

            if (object.bulkEditMode) {
                var cb = _gen(object.id, object.data.status);

                if (cb.data('checked') == 'N') {
                    var elm = cb.get(0);
                    elm.indeterminate = elm.readOnly = true;
                }

                $(list_elm).prepend(cb);

                $(list_elm).toggleClass('no-bold', true);
            }

            object.context = object.data.content;
        }

        function _gen (id, status) {
            return $('<input '
                + 'class="select2__category-status-checkbox cm-tristate tristate-checkbox-cursor" '
                + 'type="checkbox" '
                + 'data-ca-category-id="' + id + '" '
                + 'data-ca-tristate-process="false" '
                + 'data-ca-tristate-just-click="' + (status != 'N' ? 'true' : 'false') + '" '
                + 'data-checked="' + (status ? status : 'D') + '" '
                + ( status == 'A' || status == undefined ? 'checked="checked" ' : '' )
                + '/>');
        }
    });

    // Hook add_js_items
    $.ceEvent('on', 'ce.picker_add_js_items', function (picker, items, data) {
        var $select2_selectbox = $('[data-ca-picker-id="' + data.root_id + '"]'),
            category_ids = Object.keys(items).map(function (category_id) {
                return category_id;
            });
        
        category_map = {};
        if (bulkEditMode) {
            Object.keys(items).forEach(function (category_id) {
                category_map[category_id] = { status: 'A' };
            });
        }

        if (category_ids.length && $select2_selectbox.length) {
            $.map(items, function (data, category_id) {
                $.each($select2_selectbox, function (key, selectbox) {
                    var $selectbox = $(selectbox),
                        selected_ids = $selectbox.val() || null;

                    if (!Array.isArray(selected_ids)) {
                        selected_ids = [selected_ids];
                    }

                    if (selected_ids.indexOf(category_id) === -1) {
                        var option = new Option(data.category, category_id, true, true);
                        $selectbox
                            .append(option)
                            .trigger('change');
                    }
                });
            });

            fn_actualize_selected_categories_list_data(category_ids, $select2_selectbox, category_map);
        }
    });

    $.ceEvent('on', 'ce.select2.init', function (elm) {
        if (elm.hasClass('cm-object-categories-add')) {
            var old_position_dropdown = elm.data('select2').dropdown._positionDropdown;

            if ( elm.data('select2').dropdown.$element.data('caBulkEditMode') ) {
                elm.data('select2').dropdown.$dropdownParent.toggleClass('fixed-select2-dropdown', true);
            }

            elm.data('select2').dropdown._positionDropdown = function () {
                old_position_dropdown.apply(this, arguments);

                if (this.$dropdown.hasClass('select2-dropdown--above')) {
                    this.$dropdownContainer.css({
                        top: this.$container.offset().top +
                            this.$container.outerHeight(false) -
                            this.$dropdown.outerHeight(false) -
                            this.$container.find('.select2-search').outerHeight()
                    });
                }
            };
        }
    });

    var fn_actualize_selected_categories_list_data = function (category_ids, $select2_selectbox, categories_map)
    {
        var data = { id: category_ids };

        if (categories_map) {
            if (Object.keys(categories_map).length) {
                data.bulk_edit_mode = true;
                data.map = categories_map;
            }
        }

        setTimeout(_sendAjax, 10);

        function _sendAjax () {
            $.ceAjax('request', fn_url('categories.get_categories_list'), {
                hidden: true,
                caching: true,
                data: data,
                callback: function (response) {
                    var category_map = {};

                    if (typeof response.objects !== 'undefined') {
                        $.each(response.objects, function (key, category) {
                            category_map[category.id] = category;
                        });

                        $.each($select2_selectbox, function (key, selectbox) {
                            var $selectbox = $(selectbox),
                                selected_ids = $selectbox.val();

                            if (!selected_ids) {
                                return;
                            }

                            if (!Array.isArray(selected_ids)) {
                                selected_ids = [selected_ids];
                            }

                            $.each(selected_ids, function (key, id) {
                                if (typeof category_map[id] !== 'undefined') {
                                    var category = category_map[id],
                                        $option = $selectbox.find('option[value=' + id + ']');

                                    $option.text(category.text);
                                    $option.data('data', $.extend($option.data('data'), category));
                                }
                            });

                            $selectbox.trigger('change');
                        });
                    }
                }
            });
        }
    };
}(Tygh, Tygh.$));
