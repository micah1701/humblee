-- Humblee CMS – PostgreSQL initial schema and seed data

CREATE TABLE IF NOT EXISTS humblee_content (
  id SERIAL PRIMARY KEY,
  type_id INTEGER NOT NULL,
  p13n_id INTEGER NOT NULL,
  page_id INTEGER NOT NULL,
  template_block_id INTEGER NOT NULL DEFAULT 0,
  content TEXT NOT NULL,
  revision_date TIMESTAMP NOT NULL DEFAULT NOW(),
  publish_date TIMESTAMP DEFAULT NULL,
  updated_by INTEGER NOT NULL,
  live SMALLINT NOT NULL
);

INSERT INTO humblee_content (id, type_id, p13n_id, page_id, content, publish_date, updated_by, live) VALUES
(1, 1, 0, 1, '<h1>Welcome to your Humblee powered website</h1>', '1970-01-01 00:00:00', 1, 1),
(2, 2, 0, 1, '{"page_title":"Welcome to Humblee!","meta_description":"","og_title":"","og_description":"","og_image":""}', '1970-01-01 00:00:00', 1, 1);

SELECT setval(pg_get_serial_sequence('humblee_content', 'id'), MAX(id)) FROM humblee_content;

CREATE TABLE IF NOT EXISTS humblee_content_p13n (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL,
  criteria TEXT NOT NULL,
  priority SMALLINT NOT NULL,
  active SMALLINT NOT NULL
);

CREATE TABLE IF NOT EXISTS humblee_content_types (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  objectkey VARCHAR(50) NOT NULL,
  description VARCHAR(255) NOT NULL,
  output_type VARCHAR(10) NOT NULL CHECK (output_type IN ('meta', 'content')),
  input_type VARCHAR(20) NOT NULL CHECK (input_type IN ('wysiwyg', 'markdown', 'textfield', 'filemanager', 'textarea', 'multifield', 'customform')),
  input_parameters TEXT NOT NULL,
  required_role_id INTEGER NOT NULL
);

INSERT INTO humblee_content_types (id, name, objectkey, description, output_type, input_type, input_parameters, required_role_id) VALUES
(1, 'Page Body', 'pagebody', 'Main content area of page', 'content', 'wysiwyg', '<textarea name="content" id="edit_content">{content}</textarea>', 1),
(2, 'SEO & Meta Tags', 'meta_tags', 'SEO and Meta tags read by search engines and social media sites', 'meta', 'customform', 'seo', 0),
(3, 'Extra Header Code', 'extraHeadCode', 'Additional hidden HTML code to be place in the header', 'meta', 'textarea', '<textarea name="content" class="textarea" id="edit_content">{content}</textarea>', 0);

SELECT setval(pg_get_serial_sequence('humblee_content_types', 'id'), MAX(id)) FROM humblee_content_types;

CREATE TABLE IF NOT EXISTS humblee_pages (
  id SERIAL PRIMARY KEY,
  parent_id INTEGER NOT NULL,
  slug VARCHAR(255) NOT NULL,
  label VARCHAR(255) NOT NULL,
  display_order INTEGER NOT NULL,
  template_id INTEGER NOT NULL,
  required_role INTEGER NOT NULL,
  active SMALLINT NOT NULL,
  start_date TIMESTAMP DEFAULT NULL,
  end_date TIMESTAMP DEFAULT NULL,
  searchable SMALLINT NOT NULL,
  display_in_sitemap SMALLINT NOT NULL
);

INSERT INTO humblee_pages (id, parent_id, slug, label, display_order, template_id, required_role, active, start_date, end_date, searchable, display_in_sitemap) VALUES
(1, 0, '', 'Homepage', 0, 2, 0, 1, NULL, NULL, 0, 1);

SELECT setval(pg_get_serial_sequence('humblee_pages', 'id'), MAX(id)) FROM humblee_pages;

CREATE TABLE IF NOT EXISTS humblee_roles (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL,
  role_type VARCHAR(10) NOT NULL CHECK (role_type IN ('access', 'task'))
);

INSERT INTO humblee_roles (id, name, description, role_type) VALUES
(1, 'login', 'Log into website', 'access'),
(2, 'admin', 'Log into admin area', 'access'),
(3, 'content', 'Create and edit content drafts', 'task'),
(4, 'publish', 'Publish content live to the site', 'task'),
(5, 'media', 'Upload, edit and delete media files', 'task'),
(6, 'pages', 'Add, remove, reorder and label pages', 'task'),
(7, 'users', 'Set access roles for all website users', 'task'),
(8, 'designer', 'Create and edit content block types and templates', 'task'),
(9, 'developer', 'Super user access', 'task');

SELECT setval(pg_get_serial_sequence('humblee_roles', 'id'), MAX(id)) FROM humblee_roles;

CREATE TABLE IF NOT EXISTS humblee_templates (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL,
  page_type VARCHAR(12) NOT NULL CHECK (page_type IN ('default', 'controller', 'view', 'link')),
  page_meta VARCHAR(255) NOT NULL,
  dynamic_uri SMALLINT NOT NULL,
  blocks VARCHAR(255) NOT NULL,
  available SMALLINT NOT NULL
);

INSERT INTO humblee_templates (id, name, description, page_type, page_meta, dynamic_uri, blocks, available) VALUES
(1, 'Default', 'Standard content page', 'view', 'default', 0, '1,2,3', 1),
(2, 'Homepage', 'Site Homepage', 'view', 'homepage', 0, '1,2,3', 0);

SELECT setval(pg_get_serial_sequence('humblee_templates', 'id'), MAX(id)) FROM humblee_templates;

CREATE TABLE IF NOT EXISTS humblee_template_blocks (
  id SERIAL PRIMARY KEY,
  template_id INTEGER NOT NULL,
  content_type_id INTEGER NOT NULL,
  label VARCHAR(255) NOT NULL DEFAULT '',
  slot_key VARCHAR(100) NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX humblee_template_blocks_template_id ON humblee_template_blocks (template_id);

CREATE TABLE IF NOT EXISTS humblee_users (
  id SERIAL PRIMARY KEY,
  email VARCHAR(127) NOT NULL,
  email_validated SMALLINT NOT NULL DEFAULT 0,
  cellphone VARCHAR(20) NOT NULL,
  cellphone_validated SMALLINT NOT NULL DEFAULT 0,
  use_twofactor_auth SMALLINT NOT NULL DEFAULT 0,
  password VARCHAR(128) NOT NULL,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(127) NOT NULL,
  active SMALLINT NOT NULL DEFAULT 0,
  logins INTEGER NOT NULL DEFAULT 0,
  last_login TIMESTAMP NOT NULL DEFAULT NOW(),
  theme_preference VARCHAR(20) NOT NULL DEFAULT 'light'
);

CREATE TABLE IF NOT EXISTS humblee_user_roles (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS humblee_validation (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  type VARCHAR(10) NOT NULL CHECK (type IN ('email', 'phone', 'sms')),
  old_value VARCHAR(255) NOT NULL,
  new_value VARCHAR(255) NOT NULL,
  token VARCHAR(128) NOT NULL,
  token_created TIMESTAMP NOT NULL DEFAULT NOW(),
  message_id VARCHAR(255) NOT NULL,
  token_accepted TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS humblee_accesslog (
  id SERIAL PRIMARY KEY,
  session_id VARCHAR(40) NOT NULL,
  user_id INTEGER NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  ip_geolocation VARCHAR(255) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
  status VARCHAR(32) NOT NULL
);

CREATE TABLE IF NOT EXISTS humblee_media (
  id SERIAL PRIMARY KEY,
  folder INTEGER NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(20) NOT NULL,
  size INTEGER NOT NULL,
  required_role INTEGER NOT NULL,
  encrypted SMALLINT NOT NULL,
  upload_by INTEGER NOT NULL,
  upload_date TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS humblee_media_folders (
  id SERIAL PRIMARY KEY,
  parent_id INTEGER NOT NULL,
  name VARCHAR(255) NOT NULL
);

INSERT INTO humblee_media_folders (id, parent_id, name) VALUES
(1, 0, 'Images'),
(2, 0, 'Other Files');

SELECT setval(pg_get_serial_sequence('humblee_media_folders', 'id'), MAX(id)) FROM humblee_media_folders;

CREATE INDEX humblee_content_page_id ON humblee_content (page_id);
CREATE INDEX humblee_content_template_block_id ON humblee_content (template_block_id);
CREATE INDEX humblee_content_types_name ON humblee_content_types (name);
CREATE INDEX humblee_pages_slug ON humblee_pages (slug);
CREATE INDEX humblee_roles_name ON humblee_roles (name);
CREATE INDEX humblee_roles_role_type ON humblee_roles (role_type);
CREATE UNIQUE INDEX uniq_email ON humblee_users (email);
CREATE INDEX humblee_users_cellphone ON humblee_users (cellphone);
CREATE INDEX humblee_media_folder ON humblee_media (folder);
CREATE INDEX humblee_media_folders_parent_id ON humblee_media_folders (parent_id);
