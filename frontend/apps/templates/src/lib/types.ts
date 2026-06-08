export interface TemplateBlock {
  id: number | null;       // null = not yet saved
  contentTypeId: number;
  label: string;
  slotKey: string;         // read-only after first save; empty = will be auto-generated
  sortOrder: number;
}

export interface Template {
  id: number;
  name: string;
  description: string;
  page_type: string;
  page_meta: string;
  controller: string;
  controller_action: string;
  default_view: string;
  dynamic_uri: number;
  available: number;
  blocks: string;
  templateBlocks: TemplateBlock[];
}

export interface Block {
  id: number;
  name: string;
  description: string;
}
