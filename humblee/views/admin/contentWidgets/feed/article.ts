export interface IArticle {
    id?: number,
    parent_id?: number,
    contents: {
        template: string,
        display_date: string, // user selected publish date, becomes table.publish_date on publish if populated
        end_date: string, // user selected archive date, becomes table.end_date on save if populated
        headline: string,
        dateline: string,
        content: string,
        image: { src: string, altText: string },
        link: { url: string, label: string, buttonClass?: string }
    },
    revision_date?: string,
    publish_date?: string,
    updated_by?: number
    updated_by_name?: string,
    updated_by_email?: string,
    first_edition?: boolean | number,   //TRUE if given article.id is the "parent" article. Otherwise, id:INT of parent ID
    latest_revision?: boolean | number, //TRUE if given article.id is the most recent revision. Otherwise, id:INT of latest revision
    latest_published?: boolean | number,//TRUE if given article.id is the most recent published revision. Otherwise, id:INT of latest published
    status?: string // human readable publication status inferred from both "active" flag and the display/end/revision/publish dates (eg, "previously published")
}

/**
 * All information about a given Article, including meta data for all revisions
 */
export interface IArticleData {
    id: number,
    selected: number, // the revisions array index for the selected article (eg <IArticle>MyArticle = <IArticleData>Articles[Revisions[Articles[selected]] ])
    revisions: [IArticle] // array including the article and all related articles
}

export class Article {

    private articleData: IArticleData = null

    /**
     * 
     * @param article populates the article<IArticleData> from one of the following:
     *  * an existing IArticleData object. OR 
     *  * a string of JSON previously converted from an existing IArticleData object. OR
     *  * an INT of a given article's ID, used to make an xhr lookup and set the IArticleData object
     */
    constructor(article?: IArticleData | string | number) {
        if (typeof article == "string") {
            this.articleData = this.fromJson(article)
        }
        else if (typeof article == "object") {
            this.articleData = article
        }
        else if (typeof article == "number") {
            this.getArticleById(article)
                .then((articleResponse) => {
                    if (typeof articleResponse == "object") {
                        this.articleData = articleResponse
                    }
                })
        }
    }

    private fromJson(article: string): IArticleData {
        return JSON.parse(article)
    }

    /**
     * Article Factory
     * @returns an empty Article object
     */
    public new(): IArticle {
        let emptyArticle: IArticle = {
            contents: {
                template: "default",
                display_date: "",
                end_date: "",
                headline: "",
                dateline: "",
                content: "",
                image: { src: "", altText: "" },
                link: { url: "", label: "" }
            },
            first_edition: true
        }
        return emptyArticle
    }

    public json(): string {
        return JSON.stringify(this.articleData)
    }
    public get(): IArticleData {
        return this.articleData
    }

    public async getArticleById(id: number, xhrPath: string = "/request/feed"): Promise<IArticleData> {
        return fetch(xhrPath + "/article/" + id, {
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

}