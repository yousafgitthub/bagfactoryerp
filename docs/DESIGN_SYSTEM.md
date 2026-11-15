# Bag Factory ERP — UI design system (Prompt 13)

Reusable Vue components live under `resources/js/Components/Erp/`. Pages use `ErpLayout` with optional dark theme (`useErpTheme` + CSS variables in `resources/css/app.css`).

## Tokens

- Light theme by default; `html.dark` switches semantic variables (`--erp-*`).
- Tailwind `darkMode: 'class'`; utility classes `erp-card`, `erp-btn-*`, `erp-input`.

## Layout

- `ErpLayout` — sidebar, header, breadcrumbs, theme toggle, notifications shell, `ErpContentContainer`.
- `ErpPageHeader` — title, description, `#actions` slot.

## Data display

- `ErpSummaryCard`, `ErpDataTable`, `ErpTableToolbar`, `ErpPagination`, `ErpStatusBadge`, `ErpAlertList`, `ErpProductionChart`.

## Forms & dialogs

- `ErpFormSection`, `ErpFormField`, `ErpFormActions`, `ErpDialog`, `ErpConfirmDialog`.

## States

- `ErpEmptyState`, `ErpLoadingState`, `ErpErrorState`, `ErpFlashAlert`.

Do not add chart/UI libraries for standard screens; extend these components for new modules.