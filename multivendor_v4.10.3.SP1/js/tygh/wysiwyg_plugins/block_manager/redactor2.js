(function ($) {
    $.Redactor.prototype.blockManager = function () {
        return {
            areHandlersSetup: false,

            isBlockSelected: false,

            selectBlock: function(uid) {
                $('.cm-select-bm-block').removeClass('select-block--active');
                $('[data-ca-block-uid="' + uid + '"]').addClass('select-block--active');
                this.blockManager.isBlockSelected = true;
                this.modal.getActionButton().trigger('click');
            },

            getTemplate: function () {
                return String()
                    + '<div>'
                        + '<section class="wysiwyg-block-selection" id="block_selection">'
                        + '<!--block_selection--></section>'
                        + '<footer>'
                            + '<button class="hidden" id="redactor-modal-button-cancel">' + this.lang.get('cancel') + '</button>'
                            + '<button class="hidden" id="redactor-modal-button-action">' + this.lang.get('insert') + '</button>'
                        + '</footer>'
                    + '</div>';
            },

            loadBlocks: function () {
                var self = this.blockManager;

                var loadBlocksHref = 'block_manager.block_selection?purpose=wysiwyg';

                $.ceAjax('request', fn_url(loadBlocksHref), {
                    caching: false,
                    hidden: true,
                    result_ids: 'block_selection'
                });

                if (self.areHandlersSetup) {
                    return;
                }

                $.ceEvent('on', 'dispatch_event_pre', function (e, jelm, processed) {
                    if (e.type !== 'click') {
                        return;
                    }

                    // select existing block
                    if (jelm.hasClass('cm-select-bm-block') || jelm.parents('.cm-select-bm-block').length) {
                        var $selectedBlock = jelm.hasClass('cm-select-bm-block')
                            ? jelm
                            : jelm.parents('.cm-select-bm-block');

                        self.selectBlock($selectedBlock.data('caBlockUid'));

                        processed.status = processed.to_return = true;
                    }

                    // create new block
                    if (jelm.hasClass('cm-create-bm-block') || jelm.parents('.cm-create-bm-block').length) {
                        var $createdBlock = jelm.hasClass('cm-create-bm-block')
                            ? jelm
                            : jelm.parents('.cm-create-bm-block');

                        var data = $createdBlock.data(),
                            blockType = data.caBlockType,
                            blockName = data.caBlockName;

                        let newBlockHref = 'block_manager.update_block?'
                            + 'block_data[type]=' + blockType
                            + '&snapping_data[grid_id]=0'
                            + '&ajax_update=1'
                            + '&r_result_ids=block_selection'
                            + '&r_url=' + encodeURIComponent(fn_url(loadBlocksHref));

                        let $newBlockDialogContainer = $('#new_block_' + blockType);
                        if (!$newBlockDialogContainer.length) {
                            $newBlockDialogContainer = $('<div id="new_block_' + blockType + '"></div>')
                                .appendTo('body');
                        }

                        $newBlockDialogContainer.ceDialog('open', {
                            href: fn_url(newBlockHref),
                            title: Tygh.tr('add_block') + ': ' + blockName,
                            destroyOnClose: true
                        });

                        self.isBlockSelected = false;

                        processed.status = processed.to_return = true;
                    }

                    // hightlight selected block
                    $.ceEvent('on', 'ce.formajaxpost_block_0_update_form', function (response) {
                        if (!response.block_data || self.isBlockSelected) {
                            return;
                        }

                        self.selectBlock(response.block_data.unique_id);
                    });
                });

                self.areHandlersSetup = true;
            },

            init: function () {
                var toolbarButton = this.button.add('blockManager', Tygh.tr('block_manager'));
                this.button.setIcon(toolbarButton, '<i class="redactor-custom-icon icon-magic icon-dark"></i>');
                this.button.addCallback(toolbarButton, this.blockManager.openPicker);
            },

            openPicker: function () {
                this.modal.addTemplate('blockManager', this.blockManager.getTemplate());
                this.modal.addCallback('blockManager', this.blockManager.loadBlocks);

                this.modal.load('blockManager', fn_strip_tags(Tygh.tr('select_block')), 'auto');
                this.modal.getModal().addClass('redactor-modal-body--block-selection');

                var $actionButton = this.modal.getActionButton();
                $actionButton.on('click', this.blockManager.insert);

                this.selection.save();

                this.blockManager.isBlockSelected = false;

                this.modal.show();
            },

            insert: function () {
                var $block = $('.select-block--active', this.modal.getModal()),
                    blockData = $block.data();

                var $lastDialog;
                do {
                    $lastDialog = $.ceDialog('get_last');
                    $lastDialog.ceDialog('close');
                } while ($lastDialog.length);

                this.modal.close();
                this.placeholder.hide();

                this.buffer.set();

                this.air.collapsed();
                this.insert.html('<p><b'
                    + ' title="' + blockData.caBlockName + '"'
                    + ' class="wysiwyg-block-loader cm-block-loader cm-block-loader--' + blockData.caBlockUid +'"'
                    + ' ></b></p>'
                );

                this.selection.restore();
            }
        }
    }
})(jQuery);
