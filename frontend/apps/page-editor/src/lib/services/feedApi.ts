import type { ArticleData, ArticleContents, FeedSaveResponse } from '../types/editor';

export const createFeedApi = (appPath: string) => {
  const xhrPath = appPath + 'core-request/feed';

  return {
    async getArticle(id: number): Promise<ArticleData> {
      const response = await fetch(`${xhrPath}/article/${id}`, {
        headers: { 'content-type': 'application/json;charset=UTF-8' },
      });
      if (!response.ok) throw new Error('Failed to load article');
      return response.json();
    },

    async saveArticle(payload: {
      id: number;
      newDraft: boolean;
      parent_id: number | boolean;
      publish: boolean;
      articleEdits: { contents: ArticleContents };
      hmac_token: string;
      hmac_key: string;
    }): Promise<FeedSaveResponse> {
      const response = await fetch(`${xhrPath}/save`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      return response.json();
    },
  };
};
