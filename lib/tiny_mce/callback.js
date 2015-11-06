function fileBrowserCallBack(field_name, url, type, win) {
    var connector = "../../filemanager/browser.html?Connector=connectors/php/connector.php";
    var enableAutoTypeSelection = true;
    
    var cType;
    tinyfck_field = field_name;
    tinyfck = win;
    
    switch (type) {
        case "image":
            cType = "Image";
            break;
        case "flash":
            cType = "Flash";
            break;
        case "file":
            cType = "File";
            break;
    }
    
    if (enableAutoTypeSelection && cType) {
        connector += "&Type=" + cType;
    }
    
    window.open(connector, "tinyfck", "modal,width=600,height=400");
}