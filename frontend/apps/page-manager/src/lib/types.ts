export interface PageFlat {
  id: number;
  label: string;
  slug: string;
  parentId: number;
  displayOrder: number;
  active: boolean;
  displayInSitemap: boolean;
  templateId: number;
  requiredRole: number;
}

export interface PageNode extends PageFlat {
  children: PageNode[];
}

export interface Template {
  id: number;
  name: string;
  available: boolean;
}

export interface Role {
  id: number;
  name: string;
}

export interface FlatItem {
  id: number;
  label: string;
  slug: string;
  active: boolean;
  displayInSitemap: boolean;
  level: number;
  hasChildren: boolean;
  parentId: number;
}
