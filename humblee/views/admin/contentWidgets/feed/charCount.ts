/**
 * Add a "N of max(N)" counter to any input/textarea field with the ".lengthcount" class and valid "maxlength" html input attribute
 * @example
 * import { charCount } from "./charCount."
 * let characterCounts = new charCount
 * characterCounts.initAll() // initiate ALL inputs with the .lengthcount class
 * characterCounts.init(document.getElementById('myInputField')) // initiate one specific instance
 * 
 * @author Micah Murray <github.com/micah1701>
 */

export class charCount {

    initAll(): void {
        let charCountInputs = document.getElementsByClassName("lengthcount") as HTMLCollectionOf<HTMLInputElement>;
        Array.from(charCountInputs).forEach((charCountInput: HTMLInputElement) => {
            this.init(charCountInput);
            charCountInput.addEventListener('keyup', () => {
                this.init(charCountInput)
            })
        })
    }

    init(element: HTMLInputElement): void {
        let current_len = element.value.length,
            max_len = Number(element.getAttribute("maxlength")),
            content = element.value,
            label_id = element.getAttribute("id") + "_count_label";

        if (current_len > max_len) {
            element.value = content.substring(0, max_len)
            current_len = max_len;
        }

        if (!document.getElementById(label_id) || document.getElementById(label_id)?.outerHTML.length == 0) {
            let labelHtml: HTMLParagraphElement = document.createElement("p");
            labelHtml.className = "help";
            labelHtml.id = label_id;
            element.parentElement?.append(labelHtml);
        }

        let labelElement = document.getElementById(label_id) as HTMLElement;
        labelElement.innerText = current_len + " of " + max_len + " characters."
    }
}


