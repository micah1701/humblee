export interface mediaData {
    filepath: string
    id: number
    name: string
    url: string
}

export interface mediaOptions {
    app_path?: string,
    callback?: Function,
    initialFolder?: string,
    selectableFileTypes?: string
}

export class MediaManager {

    public options: mediaOptions = { app_path: '', callback: () => { }, initialFolder: '', selectableFileTypes: '' }

    constructor(InitOptions?: mediaOptions) {
        //if we haven't already, create a globally scoped clone of this object
        if (typeof globalThis.__MediaManager == "undefined") {
            Object.defineProperty(globalThis, '__MediaManager', { value: this, configurable: true, writable: true })
        }

        //if class is initiated with parameters, set them now.
        if (InitOptions != undefined && typeof InitOptions == "object") {
            this.setOptions(InitOptions);
        }
    }

    public setOptions(InitOptions: mediaOptions): void {
        this.options.app_path = (InitOptions.app_path != undefined && InitOptions.app_path != null) ? InitOptions.app_path : "/admin/media"
        this.options.callback = (InitOptions.callback != undefined && typeof InitOptions.callback == "function") ? InitOptions.callback : (selectedMedia: mediaData): void => { console.log("default callback", selectedMedia) }
        this.options.initialFolder = (InitOptions.initialFolder != undefined && InitOptions.initialFolder != null) ? InitOptions.initialFolder : ''
        this.options.selectableFileTypes = (InitOptions.selectableFileTypes != undefined && InitOptions.selectableFileTypes != null) ? InitOptions.selectableFileTypes : ''
        globalThis.__MediaManager = this; // update globally-scoped copy of this object
    }

    public open() {
        let params = ""
        if (this.options.initialFolder != null || this.options.selectableFileTypes != null) {
            params = "#callback=__MediaManager.options.callback"
            params += "|initialFolder=" + this.options.initialFolder;
            params += "|selectableFileTypes" + this.options.selectableFileTypes;
        }
        let iframe = '<iframe src="' + this.options.app_path + '?iframe=true' + params + '" border="0">'
        let html = '<div id="mediamanager" class="modal is-active">';
        html += '<div class="modal-background"></div>';
        html += '<div class="modal-card">';
        html += '<header class="modal-card-head">';
        html += '<p class="modal-card-title">Media Manager</p>';
        html += '<button class="delete" aria-label="close"></button>';
        html += '</header>'
        html += '<section class="modal-card-body">';
        html += iframe;
        html += '</section></div></div>';
        let tempElement = document.createElement('div')
        tempElement.id = "mediamanagercontainer"
        tempElement.innerHTML = html;

        document.body.appendChild(tempElement);
        document.querySelector("#mediamanager button.delete").addEventListener("click", () => {
            this.closeMediamanager()
        })
    }

    public closeMediamanager() {
        document.getElementById("mediamanagercontainer").remove()
    }

}