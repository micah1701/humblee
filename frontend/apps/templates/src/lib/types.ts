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
}

export interface Block {
  id: number;
  name: string;
  description: string;
}
