<?php

declare(strict_types=1);

namespace App\Domain\Auth;

enum Permission: string
{
    case VIEW_DASHBOARD = 'view_dashboard';
    case VIEW_EXPENSE_TOTALS = 'view_expense_totals';
    case VIEW_FUTURE_EXPENSE_TOTALS = 'view_future_expense_totals';
    case VIEW_CATEGORY_REPORT = 'view_category_report';

    case CREATE_EXPENSE = 'create_expense';
    case CREATE_INCOME = 'create_income';
    case VIEW_INDIVIDUAL_EXPENSES = 'view_individual_expenses';
    case VIEW_INCOME_TOTALS = 'view_income_totals';
    case CONFIRM_EXPENSE_PAYMENT = 'confirm_expense_payment';
    case VIEW_MONTHLY_REPORT = 'view_monthly_report';
    case VIEW_ANNUAL_REPORT = 'view_annual_report';
    case VIEW_PERIOD_REPORT = 'view_period_report';

    case MANAGE_USERS = 'manage_users';
    case CHANGE_USER_ROLES = 'change_user_roles';
    case MANAGE_BACKUPS = 'manage_backups';
    case MANAGE_DISCORD_NOTIFICATIONS = 'manage_discord_notifications';
    case MANAGE_SPREADSHEET_URL = 'manage_spreadsheet_url';
}
