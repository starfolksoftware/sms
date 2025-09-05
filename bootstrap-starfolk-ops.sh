#!/usr/bin/env bash
set -Eeuo pipefail

# bootstrap_starfolk_ops_portable.sh
# Portable: no jq, no --json, older gh compatible. Creates:
# - Owner-level Project (v2) by title (create if missing)
# - Labels
# - Milestones with deadlines
# - ALL issues (from the full list), with labels & milestones
# - Adds each issue to the Project (v2)

# You can supply OWNER/REPO via env (OWNER, REPO) or GH_REPO=owner/name.
GH_REPO=starfolksoftware/sms
PROJECT_TITLE="${1:-Starfolk Ops Platform}"

# ---------- Helpers ----------
need() { command -v "$1" >/dev/null || { echo "Missing: $1"; exit 1; }; }
need gh

# Wrapper to always pass -R if GH_REPO is set
ghr() {
  if [[ -n "${GH_REPO:-}" ]]; then
    gh -R "$GH_REPO" "$@"
  else
    if [[ -n "${OWNER:-}" && -n "${REPO:-}" ]]; then
      gh -R "$OWNER/$REPO" "$@"
    else
      gh "$@"
    fi
  fi
}

repo_slug() {
  ghr repo view --json owner,name -q '.owner.login + "/" + .name'
}

# Extract OWNER from repo slug (for Projects v2 which are owner-level)
ensure_owner() {
  if [[ -z "${OWNER:-}" || -z "${REPO:-}" ]]; then
    local slug; slug="$(repo_slug)"
    OWNER="${slug%%/*}"
    REPO="${slug##*/}"
  fi
}

say(){ printf "==> %s\n" "$*"; }

# ---------- Project (v2) ----------
# Get project number by parsing plaintext view (avoids JSON flags)
project_number_by_title() {
  local owner="$1" title="$2"
  # `gh project view` exits non-zero if not found
  if gh project view --owner "$owner" "$title" >/tmp/.proj.$$ 2>/dev/null; then
    sed -n 's/^Number:[[:space:]]*\([0-9][0-9]*\).*$/\1/p' /tmp/.proj.$$
  else
    echo ""
  fi
}

ensure_project() {
  ensure_owner
  say "Ensuring Project: $PROJECT_TITLE"
  local pn
  pn="$(project_number_by_title "$OWNER" "$PROJECT_TITLE")"
  if [[ -z "$pn" ]]; then
    # Create, then re-fetch number
    gh project create --owner "$OWNER" --title "$PROJECT_TITLE" >/dev/null
    pn="$(project_number_by_title "$OWNER" "$PROJECT_TITLE")"
    if [[ -z "$pn" ]]; then
      echo "Could not determine Project number after creation."
      exit 1
    fi
    say "Created Project #$pn"
  else
    say "Project exists: #$pn"
  fi
  PROJECT_NUMBER="$pn"
}

# ---------- Labels ----------
create_label() {
  local name="$1" color="$2" desc="${3:-}"
  # Create or edit (idempotent)
  ghr label create "$name" --color "$color" --description "$desc" >/dev/null 2>&1 || \
  ghr label edit   "$name" --color "$color" --description "$desc" >/dev/null 2>&1 || true
}

ensure_labels() {
  say "Ensuring labels…"
  # Types
  create_label "Type: Feature"     "1f883d" "New feature"
  create_label "Type: Backend"     "5319e7" "Backend work"
  create_label "Type: Frontend"    "0e8a16" "Frontend work"
  create_label "Type: Integration" "b60205" "3rd-party/API"
  create_label "Type: Infra"       "0366d6" "Infra/DevOps"
  create_label "Type: Docs"        "6a737d" "Documentation"
  create_label "Type: Chore"       "c5def5" "Maintenance/refactor"
  # Modules
  create_label "Module: Core"       "111111" "Auth/RBAC/Settings"
  create_label "Module: CRM"        "0052cc" "Contacts/Leads"
  create_label "Module: Deals"      "a2eeef" "Pipeline"
  create_label "Module: Tasks"      "5319e7" "Task mgmt"
  create_label "Module: Products"   "0e8a16" "Product hub"
  create_label "Module: Email"      "b60205" "Email marketing"
  create_label "Module: Social"     "d93f0b" "Social & Ads"
  create_label "Module: AI"         "fbca04" "AI content"
  create_label "Module: Analytics"  "1d76db" "KPI dashboard"
  create_label "Module: Webhooks"   "e99695" "Inbound/Outbound"
  create_label "Module: Knowledge"  "5319e7" "Knowledge Base / Docs"
  # Priority / Status
  create_label "P0" "b60205" "Critical"
  create_label "P1" "d93f0b" "High"
  create_label "P2" "fbca04" "Normal"
  create_label "P3" "0e8a16" "Low"
  create_label "Status: Ready"       "5319e7" "Planned"
  create_label "Status: In Progress" "0e8a16" "Doing"
  create_label "Status: Blocked"     "b60205" "Blocked"
  create_label "Status: Review"      "1f883d" "Needs review"
  create_label "Optional"            "cccccc" "Nice-to-have / future"
}

# ---------- Milestones (with due dates) ----------
# Uses REST so we can set due_on. Tries create; if it exists, updates due date.
create_or_update_ms() {
  local title="$1" due="$2"
  local slug; slug="$(repo_slug)"
  # Try create
  ghr api -X POST "repos/$slug/milestones" \
    -f title="$title" -f due_on="$due" >/dev/null 2>&1 || true
  # Find number by title (with --jq, which is supported even on older gh api)
  local num
  num="$(ghr api "repos/$slug/milestones?state=all" --jq ".[] | select(.title==\"$title\") | .number" 2>/dev/null || true)"
  if [[ -n "$num" ]]; then
    # Ensure due date is set/updated
    ghr api -X PATCH "repos/$slug/milestones/$num" -f due_on="$due" >/dev/null 2>&1 || true
  fi
}

ensure_milestones() {
  say "Ensuring milestones (with deadlines)…"
  # (Africa/Lagos timeline; GitHub stores UTC)
  create_or_update_ms "M1 – Foundation (Auth/RBAC, Starter Kit)"           "2025-09-19T23:59:00Z"
  create_or_update_ms "M2 – CRM & Deals (Lead capture, Pipeline)"          "2025-10-03T23:59:00Z"
  create_or_update_ms "M3 – Tasks & Products"                              "2025-10-17T23:59:00Z"
  create_or_update_ms "M4 – Marketing (Email + Social)"                    "2025-10-31T23:59:00Z"
  create_or_update_ms "M5 – AI + Analytics"                                "2025-11-14T23:59:00Z"
  create_or_update_ms "M6 – Webhooks + Launch Readiness"                   "2025-11-28T23:59:00Z"
  create_or_update_ms "M7 – Knowledge Base (Optional)"                     "2025-12-12T23:59:00Z"
}

# ---------- Issues ----------
create_issue() {
  local title="$1" labels="$2" milestone="$3" body="$4"
  # Create; capture the printed URL from stdout (works on all gh versions)
  local out url
  out="$(ghr issue create --title "$title" --label "$labels" --milestone "$milestone" --body "$body" 2>&1 || true)"
  # If it already exists (rerun), try to detect and skip duplicate create prompts
  url="$(printf "%s\n" "$out" | grep -Eo 'https://github.com/[^ ]+/issues/[0-9]+' | tail -n1)"
  if [[ -z "$url" ]]; then
    # Try once more without body (some old gh versions are picky); then edit:
    out="$(ghr issue create --title "$title" --label "$labels" --milestone "$milestone" 2>&1 || true)"
    url="$(printf "%s\n" "$out" | grep -Eo 'https://github.com/[^ ]+/issues/[0-9]+' | tail -n1)"
    if [[ -z "$url" ]]; then
      echo "    ! Failed to create: $title"
      echo "      $out"
      return 1
    fi
    # Add body afterwards
    local num="${url##*/}"
    ghr issue edit "$num" --body "$body" >/dev/null 2>&1 || true
  fi
  echo "$url"
}

add_issue_to_project() {
  local issue_url="$1"
  gh project item-add --owner "$OWNER" --project "$PROJECT_NUMBER" --url "$issue_url" >/dev/null 2>&1 || true
}

# ---------- Run ----------
ensure_project
ensure_labels
ensure_milestones

say "Creating issues and adding to Project…"

issue() {
  local title="$1" labels="$2" milestone="$3" body="$4"
  local url; url="$(create_issue "$title" "$labels" "$milestone" "$body")"
  if [[ -n "$url" ]]; then
    add_issue_to_project "$url"
    echo "  - $(basename "$url")  $title"
  fi
}

# ==== FULL LIST (none missed) ====

# --- User Management & Roles (M1) ---
issue "Setup Authentication & Starter Kit Integration" \
"P0,Status: Ready,Type: Infra,Module: Core" "M1 – Foundation (Auth/RBAC, Starter Kit)" "$(cat <<'EOF'
Install & configure Laravel+Vue starter kit. Verify login, registration, password reset, email.
EOF
)"
issue "Implement Role Model & Permissions" \
"P0,Status: Ready,Type: Backend,Module: Core" "M1 – Foundation (Auth/RBAC, Starter Kit)" "$(cat <<'EOF'
Add Role & Permission system (Spatie/custom). Seed Admin/Sales/Marketing; permissions: manage_clients, manage_tasks, view_dashboard, etc.
EOF
)"
issue "User Role Management UI" \
"P1,Status: Ready,Type: Frontend,Module: Core" "M1 – Foundation (Auth/RBAC, Starter Kit)" "$(cat <<'EOF'
Admin UI to list users, invite/add, assign roles, edit/remove. Admin-only access.
EOF
)"
issue "Role Permissions UI" \
"P1,Status: Ready,Type: Frontend,Module: Core" "M1 – Foundation (Auth/RBAC, Starter Kit)" "$(cat <<'EOF'
Admin UI to create roles & toggle granular permissions (checkbox matrix).
EOF
)"
issue "Middleware/Policy Enforcement" \
"P0,Status: Ready,Type: Backend,Module: Core" "M1 – Foundation (Auth/RBAC, Starter Kit)" "$(cat <<'EOF'
Policies/middleware guarding routes. Test role scenarios (e.g., manage_clients for CRM creation).
EOF
)"
issue "Audit Log (Optional)" \
"P2,Status: Ready,Type: Chore,Module: Core,Optional" "M1 – Foundation (Auth/RBAC, Starter Kit)" "$(cat <<'EOF'
Log critical actions (login, exports, deletes).
EOF
)"

# --- CRM (M2) ---
issue "Database: Contacts/Leads Schema" \
"P0,Status: Ready,Type: Backend,Module: CRM" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Migrations/models for Contact/Lead (or unified with status). Fields: name,email,phone,company,status,source; relations.
EOF
)"
issue "Contacts CRUD Backend" \
"P0,Status: Ready,Type: Backend,Module: CRM" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Controllers/services for create/read/update/delete; validation.
EOF
)"
issue "Contacts CRUD UI" \
"P0,Status: Ready,Type: Frontend,Module: CRM" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
List + search/filter; detail view (deals, activity); create/edit UX.
EOF
)"
issue "Lead Status & Conversion Logic" \
"P1,Status: Ready,Type: Backend,Module: CRM" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Statuses (Lead/Qualified/Customer). Convert to Customer action and follow-ups.
EOF
)"
issue "Import Leads (Optional)" \
"P2,Status: Ready,Type: Backend,Module: CRM,Optional" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
CSV import for bulk onboarding; duplicate handling.
EOF
)"
issue "Website Form Webhook Endpoint" \
"P1,Status: Ready,Type: Integration,Module: CRM,Module: Webhooks" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Token-verified endpoint; map payload to Lead; queue & retry.
EOF
)"
issue "Facebook Lead Ads Webhook Handling" \
"P1,Status: Ready,Type: Integration,Module: CRM,Module: Webhooks" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Subscribe/verify; map Meta payload to Lead; errors & logs.
EOF
)"
issue "Contact History Sub-feature" \
"P2,Status: Ready,Type: Frontend,Module: CRM" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Timeline on contact detail: emails, tasks, deals; cross-module fetch.
EOF
)"
issue "Validation & Edge Cases" \
"P2,Status: Ready,Type: Backend,Module: CRM" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Duplicate detection (email), missing fields, webhook retries/logging.
EOF
)"

# --- Deals (M2) ---
issue "Database: Deals Schema" \
"P0,Status: Ready,Type: Backend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Deal fields: title, contact, product, value, stage, status, expected_close_date.
EOF
)"
issue "Deals Controller & API" \
"P0,Status: Ready,Type: Backend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Create/update/list. Won→update contact; Lost→reason required.
EOF
)"
issue "Deals UI – Creation" \
"P0,Status: Ready,Type: Frontend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Form from Contact or global add; select contact & product.
EOF
)"
issue "Deals UI – Pipeline View (Kanban)" \
"P0,Status: Ready,Type: Frontend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Drag & drop between stages; show key info; instant updates.
EOF
)"
issue "Deals UI – List/Detail" \
"P1,Status: Ready,Type: Frontend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Table with filters; detail with timeline & links.
EOF
)"
issue "Deal Stage Configuration" \
"P2,Status: Ready,Type: Backend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Default stages + admin-configurable names/order.
EOF
)"
issue "Deal Won/Lost Flow" \
"P1,Status: Ready,Type: Backend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Won: set status, optional actual value, activity/notify. Lost: capture reason; move closed.
EOF
)"
issue "Sales Reports (Basic)" \
"P2,Status: Ready,Type: Frontend,Module: Deals" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Header stats: open count/sum, won this month, win rate.
EOF
)"
issue "Notification on Deal Events" \
"P2,Status: Ready,Type: Integration,Module: Deals,Module: Webhooks" "M2 – CRM & Deals (Lead capture, Pipeline)" "$(cat <<'EOF'
Notify on Won/Lost; hook into webhooks/Slack/email.
EOF
)"

# --- Tasks (M3) ---
issue "Database: Tasks Schema" \
"P0,Status: Ready,Type: Backend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Task fields: title, desc, due_date, status, priority, assignee_id, creator_id, product/contact link, project/tag.
EOF
)"
issue "Task CRUD Backend" \
"P0,Status: Ready,Type: Backend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Create/edit/delete/list; defaults; permissions for editing.
EOF
)"
issue "Task Creation UI" \
"P0,Status: Ready,Type: Frontend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Form with assignee pick, due date, priority, product/contact links.
EOF
)"
issue "Task List UI (My/All/Overdue)" \
"P0,Status: Ready,Type: Frontend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Views for My Tasks, grouped, overdue highlight; quick complete.
EOF
)"
issue "Kanban Board UI (Tasks)" \
"P1,Status: Ready,Type: Frontend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Board by status (To Do/In Progress/Done) with drag & drop.
EOF
)"
issue "Task Detail & Comments" \
"P1,Status: Ready,Type: Frontend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Detail pane with description, files, comments, history; @mentions.
EOF
)"
issue "File Attachment on Tasks" \
"P2,Status: Ready,Type: Backend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Upload/store attachments; show on task detail.
EOF
)"
issue "Task Notifications" \
"P2,Status: Ready,Type: Backend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Notify on assignment/comments; daily 'due tomorrow' digest (opt-in).
EOF
)"
issue "Project/Category Grouping" \
"P2,Status: Ready,Type: Backend,Module: Tasks" "M3 – Tasks & Products" "$(cat <<'EOF'
Project/tag grouping; filters & views.
EOF
)"
issue "Recurring/Template Tasks (Future)" \
"P3,Status: Ready,Type: Feature,Module: Tasks,Optional" "M3 – Tasks & Products" "$(cat <<'EOF'
Recurring tasks & templates (future).
EOF
)"

# --- Products (M3) ---
issue "Database: Products Schema" \
"P0,Status: Ready,Type: Backend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
Product fields: name, type, description, launch_date, status, links, version.
EOF
)"
issue "Product CRUD" \
"P0,Status: Ready,Type: Backend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
Add/edit with validation; roles: Product Manager/Admin.
EOF
)"
issue "Product UI – List" \
"P1,Status: Ready,Type: Frontend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
List with basic KPIs columns.
EOF
)"
issue "Product UI – Detail Dashboard" \
"P1,Status: Ready,Type: Frontend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
Details + related tasks/deals + product KPIs + placeholder graphs.
EOF
)"
issue "Linking Other Modules to Product" \
"P1,Status: Ready,Type: Backend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
Selectors in Task/Deal forms; filtered views by product.
EOF
)"
issue "Product Metrics Input (Admin UI)" \
"P2,Status: Ready,Type: Frontend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
Manual inputs for user count/MRR; store for tracking.
EOF
)"
issue "Product Archive/Retire" \
"P3,Status: Ready,Type: Backend,Module: Products" "M3 – Tasks & Products" "$(cat <<'EOF'
Archive flag; hide from active lists without deleting.
EOF
)"

# --- Email Marketing (M4) ---
issue "Database: Email Campaign Schema" \
"P0,Status: Ready,Type: Backend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Campaigns + Templates; recipients snapshot; schedule; stats fields.
EOF
)"
issue "Email Compose UI" \
"P0,Status: Ready,Type: Frontend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Recipients select/segments, subject/body editor, personalization, test send, schedule.
EOF
)"
issue "Email Sending Backend (Queue + SMTP)" \
"P0,Status: Ready,Type: Backend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Queued dispatch per recipient; driver config; error/bounce log.
EOF
)"
issue "Open/Click Tracking Mechanism" \
"P1,Status: Ready,Type: Backend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Pixel route; tracked redirect links; per-recipient metrics.
EOF
)"
issue "Email Campaign Tracking UI" \
"P1,Status: Ready,Type: Frontend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Sent/open/click rates; tables/graphs; recipient activity.
EOF
)"
issue "Campaign List UI" \
"P2,Status: Ready,Type: Frontend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Index of drafts/scheduled/sent; quick stats & filters.
EOF
)"
issue "Email Template Management (Optional)" \
"P2,Status: Ready,Type: Frontend,Module: Email,Optional" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
CRUD templates; save-from-campaign.
EOF
)"
issue "Bounce/Unsubscribe Handling" \
"P1,Status: Ready,Type: Backend,Module: Email" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Unsubscribe flag/route/footer; suppress in sends; optional bounce handling.
EOF
)"
issue "Two-Way Email Sync (Future)" \
"P3,Status: Ready,Type: Integration,Module: Email,Optional" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Note for future Gmail/Outlook sync.
EOF
)"

# --- Social Media & Ads (M4) ---
issue "OAuth Integration for Facebook/Instagram" \
"P0,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Meta OAuth; manage pages/IG; store tokens securely.
EOF
)"
issue "OAuth Integration for Twitter (X)" \
"P0,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
X OAuth; posting & profile read.
EOF
)"
issue "Social Account Link UI" \
"P1,Status: Ready,Type: Frontend,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Integrations screen: connect/disconnect FB/IG/X; status display.
EOF
)"
issue "Posting to Facebook/Instagram" \
"P0,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Graph API: FB Page posts; IG image+caption (business account).
EOF
)"
issue "Posting to Twitter (X)" \
"P0,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Tweet text + media via API.
EOF
)"
issue "Schedule Posts Mechanism" \
"P0,Status: Ready,Type: Backend,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
SocialPost model; scheduler/cron; publish & mark sent.
EOF
)"
issue "Social Content UI (Composer + Calendar + History)" \
"P1,Status: Ready,Type: Frontend,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Compose per platform; calendar view; history log with links.
EOF
)"
issue "Social Metrics Fetch (FB/IG)" \
"P1,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Likes/comments/shares/followers; store nightly.
EOF
)"
issue "Social Metrics Fetch (Twitter)" \
"P1,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Tweet metrics & followers; store nightly.
EOF
)"
issue "Ad Campaign Data Fetch (Meta Ads)" \
"P1,Status: Ready,Type: Integration,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Marketing API: spend, impressions, clicks, leads; map campaigns.
EOF
)"
issue "Social & Ad Analytics UI" \
"P1,Status: Ready,Type: Frontend,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Dashboard widgets for social KPIs & ad KPIs with deltas.
EOF
)"
issue "Lead Source Attribution" \
"P1,Status: Ready,Type: Backend,Module: Social,Module: Analytics" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Tag source on inbound leads; report leads by source.
EOF
)"
issue "Error Handling for API calls" \
"P2,Status: Ready,Type: Chore,Module: Social" "M4 – Marketing (Email + Social)" "$(cat <<'EOF'
Robust errors, token refresh, re-auth flows, surfaced in UI.
EOF
)"

# --- AI (M5) ---
issue "AI Provider Integration" \
"P0,Status: Ready,Type: Integration,Module: AI" "M5 – AI + Analytics" "$(cat <<'EOF'
OpenAI (or similar) credentials; secure config; SDK wrapper.
EOF
)"
issue "Content Generation Backend Service" \
"P0,Status: Ready,Type: Backend,Module: AI" "M5 – AI + Analytics" "$(cat <<'EOF'
Service accepts type+brief; prompt templates per type; returns draft.
EOF
)"
issue "Content Generation UI" \
"P0,Status: Ready,Type: Frontend,Module: AI" "M5 – AI + Analytics" "$(cat <<'EOF'
Type picker (blog, product copy, ads, social, email), fields, spinner, editable draft.
EOF
)"
issue "Edit & Save Generated Content" \
"P1,Status: Ready,Type: Backend,Module: AI" "M5 – AI + Analytics" "$(cat <<'EOF'
ContentPiece model; save drafts; relate to product; reuse actions.
EOF
)"
issue "Content Library UI" \
"P1,Status: Ready,Type: Frontend,Module: AI" "M5 – AI + Analytics" "$(cat <<'EOF'
List by type/date; copy/use into email/social flows.
EOF
)"
issue "Quality Control Mechanism (Optional)" \
"P2,Status: Ready,Type: Backend,Module: AI,Optional" "M5 – AI + Analytics" "$(cat <<'EOF'
Basic limits, empty-output retry, moderation checks.
EOF
)"
issue "Logging AI Usage" \
"P2,Status: Ready,Type: Backend,Module: AI" "M5 – AI + Analytics" "$(cat <<'EOF'
Store prompts/outputs meta for admin review.
EOF
)"
issue "AI Settings (Optional)" \
"P2,Status: Ready,Type: Frontend,Module: AI,Optional" "M5 – AI + Analytics" "$(cat <<'EOF'
Adjust temperature/length; manage templates.
EOF
)"

# --- Analytics (M5) ---
issue "Dashboard Backend Prep" \
"P0,Status: Ready,Type: Backend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Queries for internal KPIs; hooks for external; snapshot table if needed.
EOF
)"
issue "Dashboard UI Layout" \
"P0,Status: Ready,Type: Frontend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Cards for headline KPIs; line/bar/pie charts; reusable components.
EOF
)"
issue "Populate Dashboard Data" \
"P0,Status: Ready,Type: Backend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Consolidated payload; Inertia/Store setup; fast loading.
EOF
)"
issue "Interactivity (Date Ranges)" \
"P1,Status: Ready,Type: Frontend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Last 7/30/custom; refetch & redraw.
EOF
)"
issue "Drill-down Links" \
"P1,Status: Ready,Type: Frontend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Clickable widgets to filtered lists (leads, deals, tasks).
EOF
)"
issue "Customization Options" \
"P2,Status: Ready,Type: Frontend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Hide/reorder widgets (MVP: hide toggles).
EOF
)"
issue "Weekly Summary Email" \
"P1,Status: Ready,Type: Backend,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Cron compiles last-week KPIs; email to subscribers.
EOF
)"
issue "Testing Dashboard Data" \
"P2,Status: Ready,Type: Chore,Module: Analytics" "M5 – AI + Analytics" "$(cat <<'EOF'
Seeded data tests; graceful N/A on API failures.
EOF
)"

# --- Webhooks (M6) ---
issue "Outgoing Webhook Infrastructure" \
"P0,Status: Ready,Type: Backend,Module: Webhooks" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
WebhookEndpoint table; events (lead.created, deal.won, task.completed…); secret headers; retries/logs.
EOF
)"
issue "Incoming Webhook Endpoints (Lead Form)" \
"P0,Status: Ready,Type: Integration,Module: Webhooks" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
/webhooks/lead-form; token/signature verify; map to Lead; tests.
EOF
)"
issue "Incoming Webhook Endpoints (Meta Lead Ads)" \
"P1,Status: Ready,Type: Integration,Module: Webhooks" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
Subscription verify; payload mapping; errors.
EOF
)"
issue "Webhook Admin UI" \
"P1,Status: Ready,Type: Frontend,Module: Webhooks" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
Table of endpoints; enable/disable; delivery logs.
EOF
)"
issue "Integration Settings Page" \
"P1,Status: Ready,Type: Frontend,Module: Core" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
Central hub for SMTP, AI key, Meta/X connections, webhooks.
EOF
)"
issue "API Key Management (If Needed)" \
"P2,Status: Ready,Type: Backend,Module: Core" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
Secure fields, masking, usage in services.
EOF
)"
issue "Docs: README + env + runbook" \
"P2,Status: Ready,Type: Docs,Module: Core" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
Setup, deploy, backups, keys, ops runbook.
EOF
)"
issue "Hardening: RBAC audit & tests" \
"P1,Status: Ready,Type: Chore,Module: Core" "M6 – Webhooks + Launch Readiness" "$(cat <<'EOF'
Permission matrix tests; sensitive routes verified.
EOF
)"

# --- Knowledge Base (M7) ---
issue "Knowledge Base Schema" \
"P2,Status: Ready,Type: Backend,Module: Knowledge,Optional" "M7 – Knowledge Base (Optional)" "$(cat <<'EOF'
Article model: title, content, category, author, tags, versioning/updated_at.
EOF
)"
issue "Knowledge Base UI" \
"P2,Status: Ready,Type: Frontend,Module: Knowledge,Optional" "M7 – Knowledge Base (Optional)" "$(cat <<'EOF'
Browse by category; search; markdown/rich editor.
EOF
)"
issue "Article CRUD" \
"P2,Status: Ready,Type: Backend,Module: Knowledge,Optional" "M7 – Knowledge Base (Optional)" "$(cat <<'EOF'
CRUD with role-based write; read for all internal users.
EOF
)"
issue "Link with Products or Tasks" \
"P3,Status: Ready,Type: Backend,Module: Knowledge,Optional" "M7 – Knowledge Base (Optional)" "$(cat <<'EOF'
Relate articles to products or tasks/SOPs.
EOF
)"
issue "Access Control (KB)" \
"P3,Status: Ready,Type: Backend,Module: Knowledge,Optional" "M7 – Knowledge Base (Optional)" "$(cat <<'EOF'
Ensure internal-only; writer roles.
EOF
)"
issue "Search Function (KB)" \
"P3,Status: Ready,Type: Backend,Module: Knowledge,Optional" "M7 – Knowledge Base (Optional)" "$(cat <<'EOF'
Basic full-text/LIKE search across title/content.
EOF
)"

say "Done! All issues created and added to Project #$PROJECT_NUMBER."
echo "Repo:    https://github.com/$(repo_slug)"
echo "Project: https://github.com/orgs/$OWNER/projects/$PROJECT_NUMBER  (if $OWNER is an org)"
echo "         https://github.com/users/$OWNER/projects/$PROJECT_NUMBER (if $OWNER is a user)"
