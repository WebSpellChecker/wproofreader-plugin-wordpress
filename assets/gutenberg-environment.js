jQuery(document).ready(function ($){

    function isGutenbergActive() {
        return document.body.classList.contains('block-editor-page');
    }

    function isContentEditable(element){
        return element.isContentEditable;
    }

    function isInstanceCreated(element){
        return element.hasAttribute('data-wsc-instance');
    }

    function isGutenbergTableCell(element){
        return element.classList.contains('wp-block-table__cell-content');
    }

    function ignoreElement(element) {
        if (!isContentEditable(element)){
            return true;
        }

        if (isInstanceCreated(element)){
            return true;
        }

        if (isGutenbergTableCell(element)){
            return true;
        }

        return false;
    }

    function createInstance(element){
        WEBSPELLCHECKER.init({
            container: element,
        });
    }

    if (!isGutenbergActive()){
        return;
    }

    function handleGutenbergReady(){

        $(".rich-text").each(function (index) {
            const element = jQuery(this).get(0);
            if (ignoreElement(element)) {
                return;
            }
            createInstance(element);
        });

        wp.data.subscribe(function () {
            var isSidebarOpened = wp.data.select('core/edit-post').isEditorSidebarOpened();
            if (isSidebarOpened) {
                jQuery('.wsc-badge__wrapper').css('right', '300px');
            } else {
                jQuery('.wsc-badge__wrapper').css('right', '30px');
            }
        });
    }
    function handleTinyMceInit(editor) {
        const element = editor.getBody();
        if (ignoreElement(element)) {
            return;
        }
        createInstance(element);
    }
    function handleGutenbergReadyWithDelay(){
        setTimeout(handleGutenbergReady, 100);
    }

    window._wpLoadBlockEditor.then(handleGutenbergReadyWithDelay);

    if (window.tinymce) {
        tinymce.on('addeditor', function (event) {
            const editor = event.editor;
            editor.on('init', function () {
                handleTinyMceInit(editor);
            });
        });
    }

});
