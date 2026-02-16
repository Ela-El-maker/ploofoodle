# Ploofoodle Architecture (Phase 0/1 Skeleton)

## Component Diagram
```mermaid
flowchart LR
  subgraph AdminSide[Admin Side]
    B[Admin Browser]
    PAF[Ploofoodle Admin Frontend (PHP Views)]
    PAB[Ploofoodle Backend (Router/Controllers/Services/Repos)]
  end

  subgraph PublicSide[Mobile Public Side]
    M[Pimpodoodle App]
    PUB[Ploofoodle Public Endpoints]
    CACHE[(Optional Edge Cache/CDN)]
  end

  subgraph SharedData[Shared Data]
    DB[(Shared MySQL DB)]
  end

  subgraph ExistingSystem[Existing System]
    PL[Plonkadoodle APIs]
  end

  B -->|Session + CSRF| PAF
  PAF --> PAB
  PAB -->|RW admin_* tables only| DB

  M -->|GET /mobile/bootstrap,/mobile/update| PUB
  CACHE -.cache.-> PUB
  PUB -->|RO admin_* tables| DB

  M -->|normal app APIs| PL
  PL --> DB
```

## Auth/Trust Boundary
```mermaid
flowchart TB
  U1[Public Client] --> EP1[/mobile/bootstrap,/mobile/update]
  U2[Admin User] --> EP2[/admin/*,/auth/*]

  EP1 -->|No Auth| SAFE[Allowlist Output Gate + ETag + Cache Headers]
  EP2 -->|Session + CSRF + Role Check| ADMIN[Admin Controllers]

  SAFE --> DB[(Shared MySQL)]
  ADMIN --> DB
```

## Data Flows

### Admin Edit + Publish (Atomic)
```mermaid
sequenceDiagram
  participant A as Admin Browser
  participant R as Router+Middleware
  participant C as AdminConfig/AdminRelease Controller
  participant S as Service (Validate/Publish)
  participant Repo as Repository
  participant DB as MySQL
  participant Audit as Audit Service

  A->>R: POST /admin/config (action=save_draft|publish)
  R->>R: Session auth + CSRF + role
  R->>C: Authorized request
  C->>S: handleSaveOrPublish(payload, actor, ip)
  S->>S: Validate allowlist + semver + channel/platform
  alt action=publish
    S->>DB: BEGIN
    S->>Repo: upsert status=published, compute deterministic etag
    S->>Audit: append change log
    S->>DB: COMMIT
  else action=save_draft
    S->>Repo: upsert status=draft
    S->>Audit: append change log
  end
  S-->>C: success + current published version
  C-->>A: redirect/JSON success
```

### App Bootstrap Fetch with ETag
```mermaid
sequenceDiagram
  participant App as Pimpodoodle
  participant API as GET /mobile/bootstrap?platform&channel
  participant Repo as Config Repository
  participant DB as MySQL

  App->>API: GET + If-None-Match: "etag_x"
  API->>Repo: fetch published bundle(platform, channel)
  Repo->>DB: SELECT published row
  Repo-->>API: payload + etag + schema_version + updated_at

  alt etag matches
    API-->>App: 304 Not Modified + Cache-Control
  else changed
    API-->>App: 200 JSON + ETag + Cache-Control
  end
```

### Update Check (Soft Prompt + Plonkadoodle Hard Gate)
```mermaid
sequenceDiagram
  participant App as Pimpodoodle
  participant Ploo as GET /mobile/update
  participant Plonka as Existing Plonkadoodle API

  App->>Ploo: GET /mobile/update?platform=android&channel=stable
  Ploo-->>App: latest_version,min_supported_version,update_mode,rollout metadata
  alt newer version and rollout applies
    App-->>App: Soft prompt (non-blocking)
  end

  App->>Plonka: Normal API request (existing flow)
  alt Plonkadoodle returns 426 UPGRADE_REQUIRED
    Plonka-->>App: hard upgrade required
    App-->>App: Block and show mandatory update screen
  end
```

## Endpoint Contracts

### Public
- `GET /mobile/bootstrap?platform=&channel=`
- `GET /mobile/update?platform=&channel=`

Response basics:
- `success`, `schema_version`, `platform`, `channel`, `updated_at`, `config|manifest`
- `ETag` deterministic hash
- `Cache-Control: public, max-age=3600, stale-while-revalidate=86400`
- `304` when `If-None-Match` matches current ETag

### Admin (protected)
- `GET/POST /admin/config`
- `GET/POST /admin/releases`
- `GET/POST /auth/login`
- `POST /auth/logout`

Publish behavior:
- validate payload/fields
- compute deterministic ETag
- write published row in transaction
- append audit log in same transaction

## Safety Rules
- Bootstrap publish allowlist keys only:
  - `feature_flags`, `tuning`, `welcome_slides`, `support_links`, `env_label`, `cache_ttl_seconds`
- Unknown keys are rejected.
- `updated_by` is derived from session user server-side.
- Plonkadoodle remains hard-gate authority (`426`).
