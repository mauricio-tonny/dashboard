<?php

declare(strict_types=1);

namespace App\Domain\Auth;

enum Permission: string
{
    case VIEW_DASHBOARD = 'view_dashboard';
    case VIEW_EXPENSE_TOTALS = 'view_expense_totals';
    case VIEW_FUTURE_EXPENSE_TOTALS = 'view_future_expense_totals';
    case VIEW_CATEGORY_REPORT = 'view_category_report';
    case VIEW_CATEGORY_CHART_REPORT = 'view_category_chart_report';
    case VIEW_VENDOR_REPORT = 'view_vendor_report';
    case VIEW_PAID_VS_RECEIVED_REPORT = 'view_paid_vs_received_report';
    case VIEW_CASHFLOW_REPORT = 'view_cashflow_report';
    case VIEW_MARKET_REPORT = 'view_market_report';

    case CREATE_EXPENSE = 'create_expense';
    case CREATE_INCOME = 'create_income';
    case VIEW_INDIVIDUAL_EXPENSES = 'view_individual_expenses';
    case VIEW_INCOME_TOTALS = 'view_income_totals';
    case CONFIRM_EXPENSE_PAYMENT = 'confirm_expense_payment';
    case VIEW_MONTHLY_REPORT = 'view_monthly_report';
    case VIEW_ANNUAL_REPORT = 'view_annual_report';
    case VIEW_PERIOD_REPORT = 'view_period_report';

    case VIEW_SHOPPING = 'view_shopping';
    case MANAGE_SHOPPING = 'manage_shopping';
    case MANAGE_SHOPPING_SETTINGS = 'manage_shopping_settings';

    case VIEW_CONTACTS = 'view_contacts';
    case MANAGE_CONTACTS = 'manage_contacts';

    case VIEW_AUDIT_LOGS = 'view_audit_logs';
    case MANAGE_USERS = 'manage_users';
    case CHANGE_USER_ROLES = 'change_user_roles';
    case MANAGE_BACKUPS = 'manage_backups';
    case MANAGE_DISCORD_NOTIFICATIONS = 'manage_discord_notifications';
    case MANAGE_SPREADSHEET_URL = 'manage_spreadsheet_url';
}
