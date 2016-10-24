function startSpellCheck(){
    doSpell({
        ctrl: 'myEditor',
        lang: 'en_US',
        title: sCustomWSCPopupTitle,
        cmd: sWscTab,
        userDictionaryName: sUserDictionaryName,
        customDictionaryName: ['ID1','ID2'],
        schemaIdentifier: sSchemaId,
        width: sWidth,
        height: sHeight,
        top: sInitY,
        left: sInitX,
        autoClose: sAutoClose,
        onCancel: onCancel,
        onFinish: onFinish,
        onClose: onClose
    });
}