import { IArticle } from "./article.js"

export class Feed {
    //@ts-ignore because the showdown module is included from the html page outside this module
    const showdownConverter: showdown = new showdown.Converter()


    /**
     * 
     * @param id of feed. Default is the "announcements" feed
     * @param categoryIds  (optional) array of category IDs to pull from. Default, False, returns ALL articles in feed regardless of category. Otherwise, 
     * @returns array of articles
     */
    public async getFeed(id: number = 1, categoryIds: number[] | false = false): Promise<IArticle[]> {
        return fetch("/request/feed/feed/", {
            headers: {
                'content-type': 'application/json;charset=UTF-8',
            }
        })
            .then((response) => {
                if (response.ok) {
                    let fetchedData = response.json()
                    return fetchedData
                }
                else {
                    return new Error("Invalid Response")
                }
            })
            .catch((error) => {
                console.error(error)
                return new Error(error)
            })
    }

    public async drawFeed(wrapperTop: string = '<div class="announcement card">', wrapperBottom: string = '</div>'): Promise<string> {
        let html = ''
        const articles = await this.getFeed()
        for (const article of articles) {
            html += wrapperTop
            switch (article.contents.template) {
                case "cta":
                    html += this._cardCTA(article)
                    break;
                case "profile":
                    html += this._cardProfile(article)
                    break;
                case "highlight":
                    html += this._cardHightlight(article)
                    break;

                default:
                    html += this._cardDefault(article)
            }

            html += wrapperBottom
        }
        return html
    }

    /**
     * Draw article card HTML for a given article and its template
     */

    protected _cardDefault(article: IArticle): string {
        let html = '<div class="card-content">'
        html += "<h2>" + article.contents.headline + "</h2>"
        if (article.contents.dateline.trim() != "") {
            html += '<span class="">' + article.contents.dateline + '</span>'
        }
        html += this.convertMarkdown(article.contents.content)
        if (article.contents.link.url.trim() != "") {
            let linkText = (article.contents.link.label.trim() != "") ? article.contents.link.label : article.contents.link.url
            html += '<a href="' + article.contents.link.url + '">' + linkText + '</a>'
        }
        if (article.contents.image.src.trim() != "") {
            html += '<img src="' + article.contents.image.src + '" alt="' + article.contents.image.altText + '">'
        }
        html += '</div>'
        return html
    }

    protected _cardProfile(article: IArticle): string {
        let html = ''
        html += '<div class="card-content">'
        html += '<h2>' + article.contents.headline + '</h2>'
        if (article.contents.dateline.trim() != "") {
            html += '<span class="">' + article.contents.dateline + '</span>'
        }
        if (article.contents.image.src.trim() != "") {
            html += '<img src="' + article.contents.image.src + '" alt="' + article.contents.image.altText + '" style="float: right; padding: 0 0 10px 10px; max-width: 45%; width:100%; border-radius: 0 20%">'
        }
        html += this.convertMarkdown(article.contents.content)
        if (article.contents.link.url.trim() != "") {
            let linkText = (article.contents.link.label.trim() != "") ? article.contents.link.label : article.contents.link.url
            html += '<a href="' + article.contents.link.url + '">' + linkText + '</a>'
        }
        html += '</div>'
        return html
    }

    protected _cardCTA(article: IArticle): string {
        let html = ''
        html += '<div class="card-header bg-navy"><h2>' + article.contents.headline + '</h2></div>'
        html += '<div class="card-content">'
        if (article.contents.dateline.trim() != "") {
            html += '<span class="">' + article.contents.dateline + '</span>'
        }
        html += this.convertMarkdown(article.contents.content)
        if (article.contents.link.url.trim() != "") {
            let buttonClass = (article.contents.link.buttonClass.trim() != "") ? article.contents.link.buttonClass : "magenta"
            let linkText = (article.contents.link.label.trim() != "") ? article.contents.link.label : article.contents.link.url
            html += '<a class="button ' + buttonClass + '" href="' + article.contents.link.url + '">' + linkText + '</a>'
        }
        if (article.contents.image.src.trim() != "") {
            html += '<img src="' + article.contents.image.src + '" alt="' + article.contents.image.altText + '">'
        }
        html += '</div>'
        return html
    }

    protected _cardHightlight(article: IArticle): string {
        let html = ''
        html += '<div class="card-header bg-magenta"><h2>' + article.contents.headline + '</h2></div>'
        html += '<div class="card-content">'
        html += this.convertMarkdown(article.contents.content)
        if (article.contents.image.src.trim() != "") {
            html += '<img src="' + article.contents.image.src + '" alt="' + article.contents.image.altText + '">'
        }
        html += '</div>'
        return html
    }

    protected convertMarkdown(markdown: string): string {
        return this.showdownConverter.makeHtml(markdown)
    }
}

(async () => {
    const feed = new Feed;
    const html = await feed.drawFeed('<div class="grid-item grid-half"><div class="announcement card">', '</div></div>');
    document.getElementById("announcementFeed").innerHTML = html;
    let hackyFlag = document.getElementById("feedLoaded") as HTMLInputElement
    hackyFlag.value = "true"
    hackyFlag.dispatchEvent(new Event('change'))
})();