

// ============================================================
// AUTHENTIFICATION & UTILISATEURS
// ============================================================

TableGroup auth {
  users
  password_reset_tokens
  sessions
  personal_access_tokens
}

Table users {
  id bigint [pk, increment]
  name varchar [not null]
  email varchar [not null, unique]
  phone varchar [unique]
  avatar varchar
  is_active boolean [not null, default: true]
  managed_market_id bigint
  email_verified_at timestamp
  password varchar [not null]
  remember_token varchar
  created_at timestamp
  updated_at timestamp

  Note: 'Utilisateurs (visiteurs, admins, commerçants)'
}

Table password_reset_tokens {
  email varchar [pk]
  token varchar [not null]
  created_at timestamp
}

Table sessions {
  id varchar [pk]
  user_id bigint [note: 'index']
  ip_address varchar(45)
  user_agent text
  payload longtext [not null]
  last_activity int [not null, note: 'index']
}

Table personal_access_tokens {
  id bigint [pk, increment]
  tokenable_type varchar [not null]
  tokenable_id bigint [not null, note: 'index']
  name text [not null]
  token varchar(64) [not null, unique]
  abilities text
  last_used_at timestamp
  expires_at timestamp [note: 'index']
  created_at timestamp
  updated_at timestamp

  Note: 'Tokens Sanctum'
}

// ============================================================
// RÔLES & PERMISSIONS (Spatie)
// ============================================================

TableGroup permissions {
  permissions
  roles
  model_has_permissions
  model_has_roles
  role_has_permissions
}

Table permissions {
  id bigint [pk, increment]
  name varchar [not null]
  guard_name varchar [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (name, guard_name) [unique]
  }
}

Table roles {
  id bigint [pk, increment]
  name varchar [not null]
  guard_name varchar [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (name, guard_name) [unique]
  }

  Note: 'SUPER_ADMIN, ADMIN_MARCHE, COMMERCANT, etc.'
}

Table model_has_permissions {
  permission_id bigint [not null]
  model_type varchar [not null]
  model_id bigint [not null]

  indexes {
    (permission_id, model_id, model_type) [pk]
    (model_id, model_type)
  }
}

Table model_has_roles {
  role_id bigint [not null]
  model_type varchar [not null]
  model_id bigint [not null]

  indexes {
    (role_id, model_id, model_type) [pk]
    (model_id, model_type)
  }
}

Table role_has_permissions {
  permission_id bigint [not null]
  role_id bigint [not null]

  indexes {
    (permission_id, role_id) [pk]
  }
}

// ============================================================
// MARCHÉS & EMPLACEMENTS
// ============================================================

TableGroup markets_domain {
  markets
  market_blocks
  market_product_category
  places
  place_members
  place_requests
}

Table markets {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [not null, unique]
  city varchar [not null]
  location varchar
  description text
  image varchar
  cover_image varchar
  total_places int [not null, default: 0]
  occupied_places int [not null, default: 0]
  latitude decimal(10,7)
  longitude decimal(10,7)
  is_active boolean [not null, default: true]
  visit_count bigint [not null, default: 0]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp

  indexes {
    (city, is_active)
    slug
  }
}

Table market_blocks {
  id bigint [pk, increment]
  market_id bigint [not null]
  name varchar [not null]
  code varchar
  description text
  total_places int [not null, default: 0]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, name) [unique]
    (market_id, is_active)
  }
}

Table market_product_category {
  id bigint [pk, increment]
  market_id bigint [not null]
  product_category_id bigint [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, product_category_id) [unique]
  }

  Note: 'Pivot : catégories par marché'
}

Table places {
  id bigint [pk, increment]
  market_id bigint [not null]
  market_block_id bigint
  chief_user_id bigint
  number varchar [not null]
  qr_code varchar [unique]
  status varchar [not null, default: 'available']
  category varchar
  latitude decimal(10,7)
  longitude decimal(10,7)
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp

  indexes {
    (market_id, number) [unique]
    (market_id, status)
    status
  }

  Note: 'Status : available | occupied | maintenance | reserved'
}

Table place_members {
  id bigint [pk, increment]
  place_id bigint [not null]
  user_id bigint [not null]
  role varchar [not null, default: 'member']
  created_at timestamp
  updated_at timestamp

  indexes {
    (place_id, user_id) [unique]
    (place_id, role)
  }
}

Table place_requests {
  id bigint [pk, increment]
  user_id bigint [not null]
  market_id bigint [not null]
  place_id bigint
  reviewed_by bigint
  merchant_name varchar [not null]
  merchant_phone varchar [not null]
  category varchar
  description text
  status varchar [not null, default: 'pending']
  rejection_reason text
  history json
  reviewed_at timestamp
  created_at timestamp
  updated_at timestamp

  indexes {
    (status, market_id)
    user_id
  }

  Note: 'Status : pending | approved | rejected'
}

// ============================================================
// PRODUITS & CATÉGORIES
// ============================================================

TableGroup products_domain {
  product_categories
  products
  product_images
}

Table product_categories {
  id bigint [pk, increment]
  parent_id bigint
  name varchar [not null]
  slug varchar [not null, unique]
  icon varchar
  description text
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  indexes {
    (parent_id, is_active)
  }
}

Table products {
  id bigint [pk, increment]
  user_id bigint [not null]
  market_id bigint [not null]
  place_id bigint
  category_id bigint
  name varchar [not null]
  slug varchar [not null]
  description text
  price decimal(12,2) [not null]
  unit varchar [not null, default: 'unit']
  stock int [not null, default: 0]
  available boolean [not null, default: true]
  is_trending boolean [not null, default: false]
  view_count bigint [not null, default: 0]
  search_count bigint [not null, default: 0]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp

  indexes {
    (market_id, slug) [unique]
    (market_id, available)
    (user_id, available)
    category_id
    name
  }
}

Table product_images {
  id bigint [pk, increment]
  product_id bigint [not null]
  path varchar [not null]
  is_primary boolean [not null, default: false]
  sort_order smallint [not null, default: 0]
  created_at timestamp
  updated_at timestamp

  indexes {
    (product_id, is_primary)
  }
}

// ============================================================
// PAIEMENTS
// ============================================================

TableGroup payments {
  payment_receipts
}

Table payment_receipts {
  id bigint [pk, increment]
  user_id bigint [not null]
  market_id bigint
  place_id bigint
  reviewed_by bigint
  file_path varchar [not null]
  amount decimal(12,2)
  reference varchar
  status varchar [not null, default: 'pending']
  rejection_reason text
  history json
  reviewed_at timestamp
  created_at timestamp
  updated_at timestamp

  indexes {
    (status, user_id)
  }

  Note: 'Status : pending | approved | rejected'
}

// ============================================================
// COMMUNICATION & AFFICHAGE LED
// ============================================================

TableGroup communication {
  announcements
  led_displays
}

Table announcements {
  id bigint [pk, increment]
  market_id bigint [not null]
  created_by bigint
  title varchar [not null]
  content text [not null]
  show_on_led boolean [not null, default: true]
  starts_at timestamp
  expires_at timestamp
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, is_active, expires_at)
  }
}

Table led_displays {
  id bigint [pk, increment]
  market_id bigint [not null]
  display_type varchar [not null]
  payload json [not null]
  refresh_interval int [not null, default: 30]
  is_active boolean [not null, default: true]
  last_refreshed_at timestamp
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, display_type, is_active)
  }
}

// ============================================================
// ANALYTIQUES
// ============================================================

TableGroup analytics {
  product_searches
  product_views
  market_visits
}

Table product_searches {
  id bigint [pk, increment]
  product_id bigint
  market_id bigint
  user_id bigint
  query varchar [not null]
  ip_address varchar(45)
  created_at timestamp
  updated_at timestamp

  indexes {
    (query, created_at)
    market_id
  }
}

Table product_views {
  id bigint [pk, increment]
  product_id bigint [not null]
  market_id bigint
  user_id bigint
  ip_address varchar(45)
  created_at timestamp
  updated_at timestamp

  indexes {
    (product_id, created_at)
  }
}

Table market_visits {
  id bigint [pk, increment]
  market_id bigint [not null]
  user_id bigint
  ip_address varchar(45)
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, created_at)
  }
}

// ============================================================
// AUDIT & NOTIFICATIONS
// ============================================================

TableGroup audit {
  activity_logs
}

Table activity_logs {
  id bigint [pk, increment]
  user_id bigint
  action varchar [not null]
  subject_type varchar
  subject_id bigint
  properties json
  ip_address varchar(45)
  created_at timestamp
  updated_at timestamp

  indexes {
    (subject_type, subject_id)
    (user_id, created_at)
  }
}

// ============================================================
// RELATIONS CLÉS ÉTRANGÈRES
// ============================================================

Ref: users.managed_market_id > markets.id [delete: set null]

Ref: market_blocks.market_id > markets.id [delete: cascade]
Ref: places.market_id > markets.id [delete: cascade]
Ref: places.market_block_id > market_blocks.id [delete: set null]
Ref: places.chief_user_id > users.id [delete: set null]
Ref: place_members.place_id > places.id [delete: cascade]
Ref: place_members.user_id > users.id [delete: cascade]
Ref: place_requests.place_id > places.id [delete: set null]
Ref: place_requests.user_id > users.id [delete: cascade]
Ref: place_requests.market_id > markets.id [delete: cascade]
Ref: place_requests.reviewed_by > users.id [delete: set null]

Ref: market_product_category.market_id > markets.id [delete: cascade]
Ref: market_product_category.product_category_id > product_categories.id [delete: cascade]

Ref: product_categories.parent_id > product_categories.id [delete: set null]
Ref: products.user_id > users.id [delete: cascade]
Ref: products.market_id > markets.id [delete: cascade]
Ref: products.place_id > places.id [delete: set null]
Ref: products.category_id > product_categories.id [delete: set null]
Ref: product_images.product_id > products.id [delete: cascade]

Ref: payment_receipts.user_id > users.id [delete: cascade]
Ref: payment_receipts.market_id > markets.id [delete: set null]
Ref: payment_receipts.place_id > places.id [delete: set null]
Ref: payment_receipts.reviewed_by > users.id [delete: set null]

Ref: announcements.market_id > markets.id [delete: cascade]
Ref: announcements.created_by > users.id [delete: set null]
Ref: led_displays.market_id > markets.id [delete: cascade]

Ref: product_searches.product_id > products.id [delete: set null]
Ref: product_searches.market_id > markets.id [delete: set null]
Ref: product_searches.user_id > users.id [delete: set null]
Ref: product_views.product_id > products.id [delete: cascade]
Ref: product_views.market_id > markets.id [delete: set null]
Ref: product_views.user_id > users.id [delete: set null]
Ref: market_visits.market_id > markets.id [delete: cascade]
Ref: market_visits.user_id > users.id [delete: set null]

Ref: activity_logs.user_id > users.id [delete: set null]

Ref: model_has_permissions.permission_id > permissions.id [delete: cascade]
Ref: model_has_roles.role_id > roles.id [delete: cascade]
Ref: role_has_permissions.permission_id > permissions.id [delete: cascade]
Ref: role_has_permissions.role_id > roles.id [delete: cascade]
