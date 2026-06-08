export type ContentInputType = 'wysiwyg' | 'multifield' | 'customform' | 'textfield' | 'textarea' | 'markdown' | 'filemanager';

export interface ContentRecord {
  id: number;
  pageId: number;
  typeId: number;
  p13nId: number;
  templateBlockId: number;
  content: string;
  revisionDate: string;
  publishDate: string | null;
  live: boolean;
  updatedBy: number;
  updatedByName: string;
}

export interface ContentType {
  id: number;
  name: string;
  description: string;
  inputType: ContentInputType;
  inputParameters: string;
}

export interface PageData {
  id: number;
  label: string;
  active: boolean;
  url: string;
}

export interface Revision {
  id: number;
  revisionDate: string;
  publishDate: string | null;
  live: boolean;
}

export interface P13nVersion {
  id: number;
  name: string;
  description: string;
}

export interface ContentTypeOption {
  id: number;
  name: string;
}

export interface TemplateSlot {
  templateBlockId: number;
  slotKey: string;
  label: string;
  contentTypeId: number;
  contentTypeName: string;
}

export interface FeedHmac {
  token: string;
  key: string;
}

export interface PageEditorConfig {
  xhrPath: string;
  appPath: string;
  isInIframe: boolean;
  userTheme: string;
  useP13n: boolean;
  domain: string;
  content: ContentRecord;
  contentType: ContentType;
  pageData: PageData;
  revisions: Revision[];
  allContentTypes: ContentTypeOption[];
  allSlots: TemplateSlot[];
  currentTemplateBlockId: number;
  allP13nVersions: P13nVersion[];
  feedHmac: FeedHmac | null;
}

// Feed/Article types (mirrored from contentWidgets/feed/article.ts)
export interface ArticleContents {
  template: string;
  display_date: string;
  end_date: string;
  headline: string;
  dateline: string;
  content: string;
  image: { src: string; altText: string };
  link: { url: string; label: string; buttonClass?: string };
}

export interface ArticleRevision {
  id: number;
  parent_id?: number;
  contents: ArticleContents;
  revision_date: string;
  publish_date?: string | null;
  updated_by?: number;
  updated_by_name?: string;
  first_edition?: boolean | number;
  latest_revision?: boolean | number;
  latest_published?: boolean | number;
  status?: string;
}

export interface ArticleData {
  id: number;
  selected: number;
  revisions: ArticleRevision[];
}

// Latest-revision-date API response
export interface LatestRevisionResponse {
  success: boolean;
  error?: string;
  content?: {
    revision_date: string;
    live: number;
    name: string;
  };
}

// Feed save API response
export interface FeedSaveResponse {
  success: boolean;
  new_id?: number;
  error?: string;
}

// Multifield input definition (from input_parameters JSON)
export interface MultifieldInput {
  label: string;
  input: string;
}
