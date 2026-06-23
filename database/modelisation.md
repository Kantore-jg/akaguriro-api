# Modélisation de la base de données — AKAGURIRO API

Schéma généré à partir des migrations Laravel (`database/migrations/`).

## Utilisation sur dbdiagram.io

1. Ouvrir [https://dbdiagram.io](https://dbdiagram.io)
2. Créer un nouveau diagramme
3. Coller le code DBML ci-dessous dans l'éditeur
4. Le diagramme ER se génère automatiquement

> **État final des migrations** : la colonne `markets.category_tags` (JSON) et les tables `category_tags` / `market_category_tag` ont été remplacées par la table pivot `market_product_category`.

---

## Code DBML complet

```dbml
// ============================================================
// AKAGURIRO API — Schéma de base de données
// Généré depuis les migrations Laravel (état final)
// ============================================================

Project akaguriro_api {
  database_type: 'MySQL'
  Note: '''
    Plateforme de gestion des marchés (AKAGURIRO).
    Auth : Laravel Sanctum + Spatie Permission.
  '''
}

// ------------------------------------------------------------
// AUTHENTIFICATION & UTILISATEURS
// ------------------------------------------------------------

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
  phone varchar [unique, note: 'nullable']
  avatar varchar [note: 'nullable']
  is_active boolean [not null, default: true]
  managed_market_id bigint [note: 'nullable — marché géré par ADMIN_MARCHE']
  email_verified_at timestamp [note: 'nullable']
  password varchar [not null]
  remember_token varchar [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  Note: 'Utilisateurs de la plateforme (visiteurs, admins, commerçants).'
}

Table password_reset_tokens {
  email varchar [pk]
  token varchar [not null]
  created_at timestamp [note: 'nullable']
}

Table sessions {
  id varchar [pk]
  user_id bigint [note: 'nullable, index']
  ip_address varchar(45) [note: 'nullable']
  user_agent text [note: 'nullable']
  payload longtext [not null]
  last_activity int [not null, note: 'index']
}

Table personal_access_tokens {
  id bigint [pk, increment]
  tokenable_type varchar [not null, note: 'morph — ex. App\\Models\\User']
  tokenable_id bigint [not null, note: 'morph, index']
  name text [not null]
  token varchar(64) [not null, unique]
  abilities text [note: 'nullable']
  last_used_at timestamp [note: 'nullable']
  expires_at timestamp [note: 'nullable, index']
  created_at timestamp
  updated_at timestamp

  Note: 'Tokens Sanctum pour l''API.'
}

// ------------------------------------------------------------
// RÔLES & PERMISSIONS (Spatie)
// ------------------------------------------------------------

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

  Note: 'Rôles : SUPER_ADMIN, ADMIN_MARCHE, COMMERCANT, etc.'
}

Table model_has_permissions {
  permission_id bigint [not null, ref: > permissions.id]
  model_type varchar [not null]
  model_id bigint [not null]

  indexes {
    (permission_id, model_id, model_type) [pk]
    (model_id, model_type)
  }
}

Table model_has_roles {
  role_id bigint [not null, ref: > roles.id]
  model_type varchar [not null]
  model_id bigint [not null]

  indexes {
    (role_id, model_id, model_type) [pk]
    (model_id, model_type)
  }
}

Table role_has_permissions {
  permission_id bigint [not null, ref: > permissions.id]
  role_id bigint [not null, ref: > roles.id]

  indexes {
    (permission_id, role_id) [pk]
  }
}

// ------------------------------------------------------------
// MARCHÉS & EMPLACEMENTS
// ------------------------------------------------------------

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
  location varchar [note: 'nullable']
  description text [note: 'nullable']
  image varchar [note: 'nullable']
  cover_image varchar [note: 'nullable']
  total_places int [not null, default: 0]
  occupied_places int [not null, default: 0]
  latitude decimal(10,7) [note: 'nullable']
  longitude decimal(10,7) [note: 'nullable']
  is_active boolean [not null, default: true]
  visit_count bigint [not null, default: 0]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [note: 'soft delete']

  indexes {
    (city, is_active)
    slug
  }
}

Table market_blocks {
  id bigint [pk, increment]
  market_id bigint [not null, ref: > markets.id]
  name varchar [not null]
  code varchar [note: 'nullable']
  description text [note: 'nullable']
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
  market_id bigint [not null, ref: > markets.id]
  product_category_id bigint [not null, ref: > product_categories.id]
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, product_category_id) [unique]
  }

  Note: 'Pivot : catégories de produits proposées par marché.'
}

Table places {
  id bigint [pk, increment]
  market_id bigint [not null, ref: > markets.id]
  market_block_id bigint [note: 'nullable', ref: > market_blocks.id]
  number varchar [not null]
  qr_code varchar [unique, note: 'nullable']
  status varchar [not null, default: 'available', note: 'available | occupied | maintenance | reserved']
  category varchar [note: 'nullable']
  latitude decimal(10,7) [note: 'nullable']
  longitude decimal(10,7) [note: 'nullable']
  chief_user_id bigint [note: 'nullable — chef de place', ref: > users.id]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [note: 'soft delete']

  indexes {
    (market_id, number) [unique]
    (market_id, status)
    status
  }
}

Table place_members {
  id bigint [pk, increment]
  place_id bigint [not null, ref: > places.id]
  user_id bigint [not null, ref: > users.id]
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
  user_id bigint [not null, ref: > users.id]
  market_id bigint [not null, ref: > markets.id]
  place_id bigint [note: 'nullable', ref: > places.id]
  merchant_name varchar [not null]
  merchant_phone varchar [not null]
  category varchar [note: 'nullable']
  description text [note: 'nullable']
  status varchar [not null, default: 'pending', note: 'pending | approved | rejected']
  reviewed_by bigint [note: 'nullable', ref: > users.id]
  reviewed_at timestamp [note: 'nullable']
  rejection_reason text [note: 'nullable']
  history json [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (status, market_id)
    user_id
  }
}

// ------------------------------------------------------------
// PRODUITS & CATÉGORIES
// ------------------------------------------------------------

TableGroup products_domain {
  product_categories
  products
  product_images
}

Table product_categories {
  id bigint [pk, increment]
  parent_id bigint [note: 'nullable — auto-référence', ref: > product_categories.id]
  name varchar [not null]
  slug varchar [not null, unique]
  icon varchar [note: 'nullable']
  description text [note: 'nullable']
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  indexes {
    (parent_id, is_active)
  }
}

Table products {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  market_id bigint [not null, ref: > markets.id]
  place_id bigint [note: 'nullable', ref: > places.id]
  category_id bigint [note: 'nullable', ref: > product_categories.id]
  name varchar [not null]
  slug varchar [not null]
  description text [note: 'nullable']
  price decimal(12,2) [not null]
  unit varchar [not null, default: 'unit']
  stock int [not null, default: 0]
  available boolean [not null, default: true]
  is_trending boolean [not null, default: false]
  view_count bigint [not null, default: 0]
  search_count bigint [not null, default: 0]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [note: 'soft delete']

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
  product_id bigint [not null, ref: > products.id]
  path varchar [not null]
  is_primary boolean [not null, default: false]
  sort_order smallint [not null, default: 0]
  created_at timestamp
  updated_at timestamp

  indexes {
    (product_id, is_primary)
  }
}

// ------------------------------------------------------------
// PAIEMENTS
// ------------------------------------------------------------

TableGroup payments {
  payment_receipts
}

Table payment_receipts {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  market_id bigint [note: 'nullable', ref: > markets.id]
  place_id bigint [note: 'nullable', ref: > places.id]
  file_path varchar [not null]
  amount decimal(12,2) [note: 'nullable']
  reference varchar [note: 'nullable']
  status varchar [not null, default: 'pending', note: 'pending | approved | rejected']
  reviewed_by bigint [note: 'nullable', ref: > users.id]
  reviewed_at timestamp [note: 'nullable']
  rejection_reason text [note: 'nullable']
  history json [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (status, user_id)
  }
}

// ------------------------------------------------------------
// COMMUNICATION & AFFICHAGE LED
// ------------------------------------------------------------

TableGroup communication {
  announcements
  led_displays
  notifications
}

Table announcements {
  id bigint [pk, increment]
  market_id bigint [not null, ref: > markets.id]
  title varchar [not null]
  content text [not null]
  show_on_led boolean [not null, default: true]
  starts_at timestamp [note: 'nullable']
  expires_at timestamp [note: 'nullable']
  is_active boolean [not null, default: true]
  created_by bigint [note: 'nullable', ref: > users.id]
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, is_active, expires_at)
  }
}

Table led_displays {
  id bigint [pk, increment]
  market_id bigint [not null, ref: > markets.id]
  display_type varchar [not null]
  payload json [not null]
  refresh_interval int [not null, default: 30]
  is_active boolean [not null, default: true]
  last_refreshed_at timestamp [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, display_type, is_active)
  }
}

Table notifications {
  id uuid [pk]
  type varchar [not null]
  notifiable_type varchar [not null, note: 'morph']
  notifiable_id bigint [not null, note: 'morph, index']
  data text [not null]
  read_at timestamp [note: 'nullable']
  created_at timestamp
  updated_at timestamp
}

// ------------------------------------------------------------
// ANALYTIQUES
// ------------------------------------------------------------

TableGroup analytics {
  product_searches
  product_views
  market_visits
}

Table product_searches {
  id bigint [pk, increment]
  query varchar [not null]
  product_id bigint [note: 'nullable', ref: > products.id]
  market_id bigint [note: 'nullable', ref: > markets.id]
  user_id bigint [note: 'nullable', ref: > users.id]
  ip_address varchar(45) [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (query, created_at)
    market_id
  }
}

Table product_views {
  id bigint [pk, increment]
  product_id bigint [not null, ref: > products.id]
  market_id bigint [note: 'nullable', ref: > markets.id]
  user_id bigint [note: 'nullable', ref: > users.id]
  ip_address varchar(45) [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (product_id, created_at)
  }
}

Table market_visits {
  id bigint [pk, increment]
  market_id bigint [not null, ref: > markets.id]
  user_id bigint [note: 'nullable', ref: > users.id]
  ip_address varchar(45) [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (market_id, created_at)
  }
}

// ------------------------------------------------------------
// AUDIT
// ------------------------------------------------------------

TableGroup audit {
  activity_logs
}

Table activity_logs {
  id bigint [pk, increment]
  user_id bigint [note: 'nullable', ref: > users.id]
  action varchar [not null]
  subject_type varchar [note: 'nullable — polymorphique']
  subject_id bigint [note: 'nullable — polymorphique']
  properties json [note: 'nullable']
  ip_address varchar(45) [note: 'nullable']
  created_at timestamp
  updated_at timestamp

  indexes {
    (subject_type, subject_id)
    (user_id, created_at)
  }
}

// ------------------------------------------------------------
// INFRASTRUCTURE LARAVEL (cache, files d'attente)
// ------------------------------------------------------------

TableGroup laravel_infra {
  cache
  cache_locks
  jobs
  job_batches
  failed_jobs
}

Table cache {
  key varchar [pk]
  value mediumtext [not null]
  expiration int [not null]
}

Table cache_locks {
  key varchar [pk]
  owner varchar [not null]
  expiration int [not null]
}

Table jobs {
  id bigint [pk, increment]
  queue varchar [not null, note: 'index']
  payload longtext [not null]
  attempts tinyint [not null]
  reserved_at int [note: 'nullable']
  available_at int [not null]
  created_at int [not null]
}

Table job_batches {
  id varchar [pk]
  name varchar [not null]
  total_jobs int [not null]
  pending_jobs int [not null]
  failed_jobs int [not null]
  failed_job_ids longtext [not null]
  options mediumtext [note: 'nullable']
  cancelled_at int [note: 'nullable']
  created_at int [not null]
  finished_at int [note: 'nullable']
}

Table failed_jobs {
  id bigint [pk, increment]
  uuid varchar [not null, unique]
  connection text [not null]
  queue text [not null]
  payload longtext [not null]
  exception longtext [not null]
  failed_at timestamp [not null, default: `now()`]
}

// ------------------------------------------------------------
// RELATIONS SUPPLÉMENTAIRES (clés étrangères)
// ------------------------------------------------------------

Ref: users.managed_market_id > markets.id [delete: set null]

Ref: markets.id < market_blocks.market_id [delete: cascade]
Ref: markets.id < places.market_id [delete: cascade]
Ref: markets.id < place_requests.market_id [delete: cascade]
Ref: markets.id < products.market_id [delete: cascade]
Ref: markets.id < announcements.market_id [delete: cascade]
Ref: markets.id < led_displays.market_id [delete: cascade]
Ref: markets.id < market_visits.market_id [delete: cascade]
Ref: markets.id < market_product_category.market_id [delete: cascade]

Ref: users.id < products.user_id [delete: cascade]
Ref: users.id < place_requests.user_id [delete: cascade]
Ref: users.id < place_members.user_id [delete: cascade]
Ref: users.id < payment_receipts.user_id [delete: cascade]

Ref: places.id < products.place_id [delete: set null]
Ref: places.id < place_members.place_id [delete: cascade]
Ref: places.id < place_requests.place_id [delete: set null]
Ref: places.chief_user_id > users.id [delete: set null]

Ref: product_categories.id < products.category_id [delete: set null]
Ref: product_categories.id < market_product_category.product_category_id [delete: cascade]
Ref: product_categories.parent_id > product_categories.id [delete: set null]

Ref: products.id < product_images.product_id [delete: cascade]
Ref: products.id < product_views.product_id [delete: cascade]
Ref: products.id < product_searches.product_id [delete: set null]
```

---

## Résumé des entités métier

| Domaine | Tables | Description |
|---------|--------|-------------|
| Auth | `users`, `personal_access_tokens`, `sessions` | Comptes et tokens Sanctum |
| Rôles | `roles`, `permissions`, pivots Spatie | SUPER_ADMIN, ADMIN_MARCHE, COMMERCANT |
| Marchés | `markets`, `market_blocks`, `market_product_category` | Marchés, blocs, catégories associées |
| Emplacements | `places`, `place_members`, `place_requests` | Places, membres, demandes d'octroi |
| Produits | `product_categories`, `products`, `product_images` | Catalogue par marché/commerçant |
| Paiements | `payment_receipts` | Justificatifs de paiement |
| LED | `announcements`, `led_displays` | Annonces et écrans d'affichage |
| Analytics | `product_searches`, `product_views`, `market_visits` | Statistiques de consultation |
| Audit | `activity_logs`, `notifications` | Journalisation et notifications |

## Migrations sources

| Migration | Tables créées / modifiées |
|-----------|---------------------------|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `2026_06_16_120000_extend_users_table` | `users` (+phone, avatar, is_active, managed_market_id) |
| `2026_06_16_120014_add_foreign_keys_to_users` | FK `users.managed_market_id` |
| `2026_06_16_113929_create_permission_tables` | Spatie permission tables |
| `2026_06_16_113930_create_personal_access_tokens_table` | `personal_access_tokens` |
| `2026_06_16_120001_create_markets_table` | `markets` |
| `2026_06_16_120002_create_market_blocks_table` | `market_blocks` |
| `2026_06_16_120003_create_places_table` | `places` |
| `2026_06_16_120004_create_place_members_table` | `place_members` |
| `2026_06_16_120005_create_place_requests_table` | `place_requests` |
| `2026_06_16_120006_create_product_categories_table` | `product_categories` |
| `2026_06_16_120007_create_products_table` | `products` |
| `2026_06_16_120008_create_product_images_table` | `product_images` |
| `2026_06_16_120009_create_payment_receipts_table` | `payment_receipts` |
| `2026_06_16_120010_create_announcements_table` | `announcements` |
| `2026_06_16_120011_create_led_displays_table` | `led_displays` |
| `2026_06_16_120012_create_analytics_tables` | `product_searches`, `product_views`, `market_visits` |
| `2026_06_16_120013_create_activity_logs_table` | `activity_logs` |
| `2026_06_16_120015_create_notifications_table` | `notifications` |
| `2026_06_16_160000_create_category_tags_tables` | `category_tags`, `market_category_tag` *(supprimées ensuite)* |
| `2026_06_16_170000_replace_category_tags_with_product_categories` | `market_product_category` *(état final)* |