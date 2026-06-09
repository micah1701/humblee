export interface RecentContentItem {
  id: number;
  pageId: number;
  pageLabel: string;
  typeName: string;
  p13nName: string | null;
  live: boolean;
  publishDate: string | null;
  revisionDate: string;
}

export interface AdminHomeConfig {
  xhrPath: string;
  appPath: string;
  userTheme: string;
  useP13n: boolean;
  recentContents: RecentContentItem[];
}

export interface PageFlat {
  id: number;
  label: string;
  parentId: number;
  displayOrder: number;
  active: boolean;
}

export interface PageNode extends PageFlat {
  children: PageNode[];
}
