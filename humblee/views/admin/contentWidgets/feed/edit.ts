import { charCount } from "./charCount.js"
import { IArticleData, IArticle, Article } from "./article.js"
import { mediaData, mediaOptions, MediaManager } from "./mediamanager.js"

const APP_PATH: string = "/"
const xhrPath: string = APP_PATH + "core-request/feed"
const mediaManager: MediaManager = new MediaManager

//@ts-ignore because the showdown module is included from the html page outside this module
const showdownConverter: showdown = new showdown.Converter()

/** Feed Article Class */
let articleClass: Article

/**Object that includes the current article AND all other revisions of the given article */
let articleData: IArticleData

/** Object of the given article (a subset of data already in articleData<IArticleData>) */
let articleContent: IArticle

/** Initially the same as articleContent<IArticle> but contains NEW values to be saved */
var articleEdits: IArticle

/**
 * Set articleData<IArticleData> and the articleContent<IArticle> subset reference with values for given article.
 * Update DOM to display given article content and revision information
 * @param articleID:number ID of requested article
 */
async function loadArticle(articleID: number) {
    articleClass = new Article
    if (articleID == 0 || isNaN(articleID)) {
        console.log("this is a new article");
        articleContent = articleClass.new()
        articleData = null
        hideElement("history_button")
        hideElement("saveAsNewDraft")
        return;
    }
    console.log("ArticleID to load is: " + articleID);
    articleData = await articleClass.getArticleById(articleID, xhrPath)
    articleContent = articleData.revisions[articleData.selected]
    populateHeader()
    populateInputs()
}

function loadFromHash(): void {
    let hash = location.hash.substring(1);
    if (isNaN(Number(hash))) {
        alert("Invalid Article ID in location hash");
        return;
    }
    loadArticle(Number(hash));
}

// listen for changes to location #hash and update content appropriately 
window.addEventListener('hashchange', () => { loadFromHash() });

// Init listeners once rest of document has loaded
document.addEventListener("DOMContentLoaded", () => {

    //load the article, if specified in URL#hash
    loadFromHash();

    //handle clicking the "Select Image" button
    let mediaButton: HTMLButtonElement = document.getElementById('article_image_picker') as HTMLButtonElement
    mediaButton.addEventListener('click', (e) => {
        e.preventDefault()
        mediaManager.setOptions(<mediaOptions>{
            callback: (fileData: mediaData): void => {
                let imageSrc = document.getElementById("article_image_src") as HTMLInputElement
                imageSrc.value = encodeURI(window.location.protocol + "//" + window.location.hostname + fileData.url)
                imageSrc.dispatchEvent(new Event('change'))
            },
            initialFolder: "1"
        })
        mediaManager.open()
    })

    document.getElementById('save').addEventListener('click', (e) => {
        e.preventDefault()
        save()
    })
    document.getElementById('saveAsNewDraft').addEventListener('click', (e) => {
        e.preventDefault()
        save(false, true)
    })
    document.getElementById('publish').addEventListener('click', (e) => {
        e.preventDefault()
        save(true)
    })

    // initiate character count and max-length UI for given input fields by class
    let characterCounter = new charCount
    characterCounter.initAll()

    // initiate listeners to update the "preview" elements when article inputs are updated
    initPreviewFields()

    // listen for changes to "template" select menu
    const templateSelect = document.getElementById('article_template') as HTMLSelectElement
    templateSelect.addEventListener('change', () => {
        changePreviewTemplate(templateSelect.value)
    })

    // listen for revision history change
    const historySelect = document.getElementById("revisionList") as HTMLSelectElement
    historySelect.addEventListener("change", () => {
        window.history.pushState(null, null, "#" + historySelect.value);
        loadArticle(parseInt(historySelect.value))
    })

    // listen for expand/collapse clicks
    const collapseTriggers = document.getElementsByClassName('collapseTrigger') as HTMLCollectionOf<HTMLDivElement>
    Array.from(collapseTriggers).forEach((trigger) => {

        trigger.addEventListener("click", (e) => {
            let targetId = trigger.dataset.collapseTarget
            let targetEl = document.getElementById(targetId)
            let hide = (!targetEl.classList.contains("collapsed"))
            hideElement(targetEl, hide, "collapsed")
        })
    })
})

/** Use "showdown" lib to convert markdown plain text to HTML */
function convertMarkdown(markdown: string): string {
    return showdownConverter.makeHtml(markdown)
}

/** For given inputs, listen for changes and set value in corresponding preview dom element */
function initPreviewFields(): void {
    //link label input field gets some special treatment so define it here
    const article_link_label = document.getElementById('article_link_label') as HTMLInputElement

    // 1 to 1 "article_field" to "preview_field" 
    let fields: string[] = ["headline", "dateline", "content", "link_label"]
    fields.forEach((field) => {
        let input = document.getElementById("article_" + field) as HTMLInputElement
        let preview = document.querySelectorAll(".preview_" + field)
        input.addEventListener("keyup", () => {
            Array.from(preview).forEach(element => {

                if (input.classList.contains("useMarkdown")) {
                    element.innerHTML = convertMarkdown(input.value)
                }
                else {
                    element.innerHTML = input.value
                }

                hideElement(element, false)

                if (input.value == "") {
                    hideElement(element)
                }

                // if this is the article_link_label field, trigger the updateLinkLabel() function nested in this function
                if (field == "link_label") {
                    updateLinkLabel()
                }
            })
        })
    })

    //when image path is changed, update coorisponding preview elements
    const imageSrc: HTMLInputElement = document.getElementById('article_image_src') as HTMLInputElement
    imageSrc.addEventListener('change', () => {
        let hide: boolean = (imageSrc.value == "")
        document.querySelectorAll(".preview_image")?.forEach((img: HTMLImageElement) => {
            img.setAttribute("src", imageSrc.value)
            hideElement(img, hide)
        })
    })

    //when a link URI/URL is changed, update coorisponding preview elements
    const linkURI: HTMLInputElement = document.getElementById('article_link_url') as HTMLInputElement
    linkURI.addEventListener('keyup', () => {
        let hide: boolean = (linkURI.value == "")
        document.querySelectorAll(".preview_link")?.forEach((a: HTMLAnchorElement) => {
            let linkClass = (linkURI.value.charAt(0) != "/" && !linkURI.value.includes("intranet.scapharma.com")) ? "outbound" : "internal"
            let target = (linkClass == "outbound") ? "_blank" : "_self"
            a.setAttribute("href", linkURI.value)
            a.setAttribute("target", target)
            a.classList.remove("outbound")
            a.classList.add(linkClass)
            hideElement(a, hide)
        })
        updateLinkLabel()
    })

    function updateLinkLabel() {
        if (article_link_label.value.trim() == "" && linkURI.value.trim() != "") {
            document.querySelectorAll(".preview_link_label")?.forEach((label: HTMLElement) => {
                label.innerHTML = linkURI.value
                hideElement(label, false)
            })
        }
    }
}

/** Hide or show specified preview template */
function changePreviewTemplate(template: string): void {
    //hide all previews
    Array.from(document.getElementsByClassName("template")).forEach((el) => { hideElement(el) })
    //then unhide the selected preview
    let preview = document.getElementById("preview_template_" + template) as HTMLDivElement
    hideElement(preview, false)

    switch (template) {
        case 'profile':
            hideElement('dateline_container')
            hideElement('image_container', false)
            hideElement('link_container', false)
            break
        case 'highlight':
            hideElement('dateline_container')
            hideElement('image_container', false)
            hideElement('link_container')
            break

        default:
            hideElement('dateline_container', false)
            hideElement('image_container', false)
            hideElement('link_container', false)
    }
}

/**
 * Hide or Show a DOM Element
 * @param el_id DOM ID of an HTMLElement or the Element itself
 * @param showOrHide (optional) True: Hide (default); False: Show
 * @param className (optional) css class value that shows or hides the given element 
*/
function hideElement(el_id: string | Element, showOrHide: boolean = true, className: string = "is-hidden"): void {
    let el_classList: DOMTokenList = (typeof el_id == "string") ? document.getElementById(el_id).classList : el_id.classList
    if (!showOrHide && el_classList.contains(className)) {
        el_classList.remove(className)
    }
    else if (showOrHide === true && !el_classList.contains(className)) {
        el_classList.add(className)
    }
}

/** Draw the revision status and other meta data about this article's instance */
function populateHeader(): void | boolean {
    if (articleData == null || typeof articleData != "object") {
        console.log("no valid aricleData object")
        return false
    }

    let revisionDateHeader = document.getElementById("revisionDate") as HTMLDivElement
    revisionDateHeader.innerHTML = ('<strong>Saved:</strong> ' + articleContent.revision_date + ' &nbsp; <strong>By:</strong> ' + articleContent.updated_by_name)

    let suppressRevisionWarning: boolean = (articleContent.latest_revision === true)
    hideElement("newerRevisionWarning", suppressRevisionWarning)

    let statusTooltip: string, revStatusHTML: string, revStatusClass: string, revStatusDescription: string
    if (articleContent.status == "Draft") {
        revStatusClass = "has-text-info"
        revStatusHTML = 'Unpublished Draft.'
        revStatusDescription = ''
        statusTooltip = 'This content has not yet been published'
    }
    else {
        statusTooltip = "This revision was published " + articleContent.publish_date
        if (articleContent.latest_published === true) {
            revStatusHTML = "Active Article."
            revStatusClass = "has-text-success"

            switch (articleContent.status) {
                case "Published Future":
                    revStatusDescription = "(Future Publication Date)"
                    break
                case "Published Expired":
                    revStatusDescription = '<span class="has-text-danger">Expired</span>'
                    break
                default:
                    revStatusDescription = 'Live on site'
            }
        }
        else {
            revStatusClass = "has-text-danger"
            revStatusHTML = 'Previously Published.'
            revStatusDescription = 'Content supplanted by a more recently published revision'
        }
    }

    let revisionStatusHeader = document.getElementById("revisionStatus") as HTMLDivElement
    revisionStatusHeader.innerHTML = '<span class="tooltip ' + revStatusClass + '" data-tooltip="' + statusTooltip + '">' + revStatusHTML + ' </span> ' + revStatusDescription

    // Update the Revision History Select Dropdown
    let historySelect = document.getElementById("revisionList") as HTMLSelectElement
    Array.from(historySelect.options).forEach(element => {
        element.remove(); // remove existing options
    });

    articleData.revisions.forEach((revision) => {
        let status: string = revision.status
        if (revision.latest_published === true) {
            status += " \u2014 LIVE"
        }
        else if (status != "Draft") {
            status += " \u2014 Replaced"
        }

        let option = document.createElement("option")
        option.value = "" + revision.id
        option.text = revision.revision_date + " (" + status + ")"
        option.selected = (revision.id == articleData.id)
        historySelect.appendChild(option)
    })

    hideElement("history_button", false)
    hideElement("saveAsNewDraft", false)

    //suppress "Save Draft" button for "published" articles, because all previously published articles are forked to a new revision on save
    if (articleContent.status == "Published") {
        hideElement("save", true)
    }
}

function populateInputs(): void | boolean {
    if (articleContent.contents == null || articleContent.contents == undefined || typeof articleContent.contents != "object") {
        return false
    }
    Object.keys(articleContent.contents).forEach((primaryKey) => {

        if (typeof articleContent.contents[primaryKey] == "object") {

            Object.keys(articleContent.contents[primaryKey]).forEach((nestedKey) => {
                let inputId = primaryKey + "_" + nestedKey
                let value = articleContent.contents[primaryKey][nestedKey]
                setValue(inputId, value)
            })
        }
        else {
            let value = articleContent.contents[primaryKey]
            setValue(primaryKey, value)
        }

        function setValue(inputId: string, value: string, prefix: string = "article_") {
            let input = document.getElementById(prefix + inputId) as HTMLInputElement
            if (input != undefined && input != null) {
                input.value = value
                input.dispatchEvent(new Event('keyup'))
                input.dispatchEvent(new Event('change'))
            }
            else {
                console.log("couldn't find form input for " + prefix + inputId)
            }

        }

    })
}

/**
 * Get the value of a form input
 * 
 * @param ID of input field
 * @returns value of input
 */
function inputValue(domID: string): string {
    let input = document.getElementById(domID) as HTMLInputElement
    return (input == null || input.value == undefined) ? "" : input.value.trim()
}

function updateArticleEdits(prefix: string = "article_"): void {
    articleEdits = {
        contents: {
            template: inputValue(prefix + 'template'),
            headline: inputValue(prefix + 'headline'),
            dateline: inputValue(prefix + 'dateline'),
            content: inputValue(prefix + 'content'),
            image: {
                src: inputValue(prefix + 'image_src'),
                altText: inputValue(prefix + 'image_altText'),
            },
            link: {
                url: inputValue(prefix + 'link_url'),
                label: inputValue(prefix + 'link_label'),
                buttonClass: "magenta"
            },
            display_date: inputValue(prefix + 'display_date'),
            end_date: inputValue(prefix + 'end_date')
        }
    }
}

/**
 * Save edits as a new revision 
 */
async function save(publish: boolean = false, newDraft: boolean = false): Promise<void> {
    if (!isDirty()) {
        quickNotice("There have been no changes made. Nothing to save.", "is-danger")
        return
    }

    //if new article (no articleContent)
    let saveID = (articleData != null && articleData.id !== undefined) ? articleData.id : 0
    let saveParent = (articleContent != null && articleContent.first_edition !== true) ? articleContent.first_edition : saveID

    let postPackage = {
        id: saveID,
        newDraft: newDraft,
        parent_id: saveParent,
        publish: publish,
        articleEdits: articleEdits,
        hmac_token: inputValue("hmac_token"),
        hmac_key: inputValue("hmac_key")
    }

    let options = {
        method: 'POST',
        body: JSON.stringify(postPackage),
        headers: {
            'Content-Type': 'application/json'
        }
    }
    const saveCall = await fetch('/core-request/feed/save', options)
    const response = await saveCall.json()
    if (response.success) {
        window.history.pushState(null, null, "#" + response.new_id);
        quickNotice("Changes Saved!")
        loadArticle(response.new_id)
    }
}

function buildFormData(formData, data, parentKey) {
    if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File)) {
        Object.keys(data).forEach(key => {
            buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key)
        })
    } else {
        const value = data == null ? '' : data

        formData.append(parentKey, value)
    }
}

function jsonToFormData(data) {
    const formData = new FormData()
    buildFormData(formData, data, null)
    return formData
}

/**
 * Check if form inputs have been changed 
 * @returns boolean TRUE if edits have made since the original content was loaded. FALSE if its the same
 */
function isDirty(): boolean {
    if (articleContent == undefined) {
        return true;
    }
    updateArticleEdits()
    return (JSON.stringify(articleContent.contents) != JSON.stringify(articleEdits.contents))
}

function quickNotice(message: string, cssClass: string = 'is-success', timeOnScreen: number = 3000) {
    let existingNotice = document.getElementById("quickNotice") as HTMLDivElement;
    if (existingNotice != null) {
        existingNotice.remove(); // remove any notice that's still showing before creating a new one.
    }

    let container = document.createElement("div") as HTMLDivElement
    container.id = "quickNotice"
    container.className = "notification has-text-centered has-text-weight-semibold " + cssClass
    container.style.cssText = "position: absolute; z-index: 100; width: 100%"
    container.innerHTML = message
    document.body.appendChild(container)

    let notice = document.getElementById("quickNotice") as HTMLDivElement;
    let startPosition = notice.clientHeight * -1;
    let animateTime = 300;
    notice.style.bottom = startPosition + 'px';
    notice.animate({ bottom: 0 }, { duration: animateTime, iterations: 1 });

    setTimeout(() => {
        notice.style.bottom = '0px'
    }, 300)
    setTimeout(() => {
        notice.animate({ bottom: startPosition + 'px' }, { duration: animateTime, iterations: 1 })
    }, timeOnScreen + animateTime)
    setTimeout(() => {
        notice.remove()
    }, timeOnScreen + animateTime + animateTime)
}