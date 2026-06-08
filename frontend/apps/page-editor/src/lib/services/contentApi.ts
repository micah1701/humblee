import type { LatestRevisionResponse } from '../types/editor';

export const createContentApi = (xhrPath: string) => ({
  async checkLatestRevision(
    pageId: number,
    contentTypeId: number,
    p13nId: number,
    templateBlockId: number = 0
  ): Promise<LatestRevisionResponse> {
    const body = new URLSearchParams({
      page_id:           String(pageId),
      content_type:      String(contentTypeId),
      p13n_id:           String(p13nId),
      template_block_id: String(templateBlockId),
    });
    const response = await fetch(`${xhrPath}content/latest-revision-date`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    });
    return response.json();
  },
});
