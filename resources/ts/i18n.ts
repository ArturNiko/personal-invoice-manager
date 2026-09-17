import { ref, watch } from 'vue';

export type Locale = 'en' | 'de';

const supportedLocales: Locale[] = ['en', 'de'];

const enTranslations = {
        app: {
            personal: 'Personal',
            invoiceManager: 'Invoice Manager',
            list: 'List',
            create: 'Create',
            profile: 'Profile',
            verificationReminder:
                'Your email address is not verified yet. You can still use the app, but please open your profile to resend the verification email.',
            navigation: {
                dashboard: 'Dashboard',
                list: 'List',
                createInvoice: 'Create invoice',
                create: 'New',
                profile: 'Profile',
                openMenu: 'Open menu',
                closeMenu: 'Close menu',
            },
            theme: {
                toLight: 'Switch to light theme',
                toDark: 'Switch to dark theme',
            },
        },
        dashboard: {
            dueThisMonth: 'Due this month',
            dueThisMonthHint: 'Pending payments due in the current month.',
            overdue: 'Overdue',
            overdueHint: 'Payments past their due date that need attention.',
            recurringCommitments: 'Recurring commitments',
            recurringCommitmentsHint:
                'Committed recurring outflow on autopilot.',
            calendarTitle: 'Calendar',
            calendarSubtitle:
                'Monthly planning view with invoice-related reminders.',
            invoices: 'invoices',
            today: 'Today',
            selectedDay: 'Selected day',
            createInvoiceForDay: 'Create invoice for this day',
            moreDayActions:
                'More day actions might be available in future updates.',
            forecast: 'Forecast',
            upcomingPayments: 'Upcoming payments',
            upcomingPaymentsDescription:
                'Monthly outflow split into committed recurring and flexible one-time payments. Click a bar to jump the calendar to that month.',
            months6: '6 months',
            months12: '12 months',
            upcoming: 'Upcoming',
            recentUpcoming: 'Recent & upcoming invoices',
            recentUpcomingDescription:
                'Next expected payment dates for active invoices.',
            noData: 'No data yet',
            noDataHint:
                'Add an invoice and upcoming payments will show up here.',
        },
        invoices: {
            listTitle: 'Invoice list',
            listSubtitle:
                'A simple overview of your invoices and their current status.',
            searchPlaceholder: 'Search invoices...',
            all: 'All',
            oneTime: 'One-time',
            recurring: 'Recurring',
            soonest: 'Soonest',
            latest: 'Latest',
            edit: 'Edit',
            delete: 'Delete',
            summary: 'Summary',
            insights: 'Invoice insights',
            insightsDescription:
                'This panel shows your current search, filter, and sort choices.',
            visibleInvoices: 'Visible invoices',
            recurringShort: 'Recurring',
            oneTimeShort: 'One-time',
            item: 'item',
            items: 'items',
            noData: 'No data yet',
            noResultsTitle: 'No invoices match your filters',
            noResultsDescription:
                'Try clearing the search, switching the type filter, or changing the sort order.',
            due: 'Due',
            id: 'ID',
            pending: 'Pending',
            paid: 'Paid',
            overdue: 'Overdue',
            deleteInvoice: 'Delete invoice?',
            deleteWarning: 'This action cannot be undone.',
            deleteConfirmation: 'Do you really want to delete',
            deleteConfirmationTail:
                'This invoice will be permanently removed and cannot be recovered.',
            keepIt: 'Keep it',
            noDueDate: 'No due date',
            title: 'Title',
            preview: 'Preview',
            createInvoice: 'Create invoice',
            createInvoiceSubtitle:
                'Add a new invoice by entering the details below or importing an existing PDF.',
            manualEntry: 'Manual Entry',
            importPdf: 'Import PDF',
            price: 'Price',
            occurrencePrice: 'Occurrence price',
            type: 'Type',
            status: 'Status',
            startDate: 'Start date',
            endDate: 'End date',
            date: 'Date',
            recurrence: 'Recurrence',
            invoicePdf: 'Invoice PDF',
            choosePdf: 'Choose PDF',
            pdfHint: 'PDF',
            recurringRangeError:
                'Recurring end date must be on or after the start date.',
            choosePdfPrompt: 'Please choose a PDF file to import.',
            invoiceProcessing: 'Invoice is being processed.',
            importFailed: 'Failed to import invoice.',
            invoiceImportPreview: 'Import invoice',
            invoicePreview: 'New invoice',
            invoiceImportDescription:
                'Choose a PDF and hand it off to the invoice reader.',
            invoiceManualDescription:
                'The form adjusts automatically for one-time and recurring invoices.',
            typeLabel: 'Type',
            statusLabel: 'Status',
            currencyLabel: 'Currency',
            recurringScheduleEndless: 'Recurring schedule: Endless',
            recurringScheduleUnset: 'Recurring schedule: not set',
            pricePreview: 'Price',
            saving: 'Saving...',
            deleteInvoiceButton: 'Delete invoice',
            deleting: 'Deleting...',
            invoiceUpdated: 'Invoice updated successfully.',
            invoiceCreated: 'Invoice created successfully.',
            invoiceDeleted: 'Invoice deleted successfully.',
            editInvoice: 'Edit invoice',
            editInvoiceSubtitle:
                'Update invoice details, status, or delete the record.',
            loadingInvoice: 'Loading invoice...',
            missingInvoiceId: 'Missing invoice id.',
            failedToLoadInvoice: 'Failed to load invoice.',
            failedToDeleteInvoice: 'Failed to delete invoice.',
            failedToUpdateInvoice: 'Failed to update invoice.',
            failedToCreateInvoice: 'Failed to create invoice.',
            importInvoice: 'Import invoice',
            importing: 'Importing...',
            saveNewInvoice: 'Create invoice',
            saveNewInvoiceLoading: 'Saving...',
            previewType: 'Type',
            previewStatus: 'Status',
            previewCurrency: 'Currency',
            previewSchedule: 'Recurring schedule',
            previewPrice: 'Price',
            previewSummary: 'Preview',
            dateLabel: 'Date',
            titlePlaceholder: 'Subscription',
            allInvoices: 'all invoices',
        },
        auth: {
            welcomeBack: 'Welcome back',
            login: 'Log in',
            email: 'Email',
            password: 'Password',
            rememberMe: 'Remember me',
            loggingIn: 'Logging in...',
            forgotPassword: 'Forgot password?',
            needAccount: 'Need an account?',
            createOne: 'Create one',
            createAccount: 'Create account',
            register: 'Register',
            registering: 'Creating account...',
            confirmPassword: 'Confirm password',
            alreadyHaveAccount: 'Already have an account?',
            passwordReset: 'Password reset',
            forgotPasswordTitle: 'Forgot password',
            sendResetLink: 'Send reset link',
            sendingResetLink: 'Sending...',
            rememberPassword: 'Remember your password?',
            backToLogin: 'Back to login',
            resetLinkSent:
                'If that account exists, a password reset link has been sent.',
            resetLinkFailed:
                'We could not send the reset link. Please try again.',
            secureAccess: 'Secure access',
            setNewPassword: 'Set a new password',
            newPassword: 'New password',
            saveNewPassword: 'Save new password',
            savingNewPassword: 'Saving...',
            needNewResetLink: 'Need a new reset link?',
            requestAnother: 'Request another',
            resetLinkInvalid:
                'The reset link is invalid or expired. Please request a new one.',
            sessionExpired:
                'Your session expired. Please refresh the page and try again.',
            somethingWentWrong: 'Something went wrong. Please try again.',
        },
        profile: {
            settingsTitle: 'Profile settings',
            settingsSubtitle:
                'Manage your account details, preferences, and security.',
            account: 'Account',
            preferences: 'Preferences',
            security: 'Security',
            dangerZone: 'Danger zone',
            logout: 'Log out',
            profileCardTitle: 'Profile',
            profileCardSubtitle: 'Your name and sign-in email address.',
            name: 'Name',
            email: 'Email',
            saveChanges: 'Save changes',
            saving: 'Saving...',
            displayCurrency: 'Display currency',
            displayCurrencySubtitle:
                'Dashboard totals and the forecast are converted into this currency.',
            preferredCurrency: 'Preferred currency',
            savePreferences: 'Save preferences',
            savePreferencesLoading: 'Saving...',
            changePassword: 'Change password',
            changePasswordSubtitle:
                'Choose a strong password you don\'t use elsewhere.',
            currentPassword: 'Current password',
            newPassword: 'New password',
            confirmNewPassword: 'Confirm new password',
            updatePassword: 'Update password',
            updatingPassword: 'Updating...',
            deleteAccount: 'Delete account',
            deleteAccountSubtitle:
                'Permanently delete your account and all associated data. This cannot be undone.',
            confirmPasswordDelete: 'Confirm your password',
            deleteAccountButton: 'Delete account',
            deletingAccount: 'Deleting...',
            resendVerification: 'Resend verification email',
            sendingVerification: 'Sending...',
            emailUnverifiedTitle: 'Your email address is not verified yet.',
            emailUnverifiedText:
                'You can still use the app, but verification helps keep your account secure.',
            emailVerificationSent: 'Verification email sent again.',
            emailVerificationFailed:
                'We could not resend the email. Please try again.',
            profileUpdated: 'Profile updated.',
            failedToUpdateProfile: 'Failed to update profile.',
            preferencesUpdated: 'Preferences updated.',
            failedToUpdatePreferences: 'Failed to update preferences.',
            passwordUpdated: 'Password updated.',
            failedToUpdatePassword: 'Failed to update password.',
            deleteAccountConfirm: 'This action is irreversible. Delete your account?',
            failedToDeleteAccount: 'Failed to delete account.',
            networkError: 'Network error.',
        },
    };

    const deTranslations = {
        app: {
            personal: 'Persönlich',
            invoiceManager: 'Rechnungsmanager',
            list: 'Liste',
            create: 'Erstellen',
            profile: 'Profil',
            verificationReminder:
                'Ihre E-Mail-Adresse wurde noch nicht verifiziert. Sie können die App weiterhin nutzen, aber bitte öffnen Sie Ihr Profil, um die Bestätigungs-E-Mail erneut zu senden.',
            navigation: {
                dashboard: 'Dashboard',
                list: 'Liste',
                createInvoice: 'Rechnung erstellen',
                create: 'Neu',
                profile: 'Profil',
                openMenu: 'Menü öffnen',
                closeMenu: 'Menü schließen',
            },
            theme: {
                toLight: 'Zum hellen Design wechseln',
                toDark: 'Zum dunklen Design wechseln',
            },
        },
        dashboard: {
            dueThisMonth: 'Diesen Monat fällig',
            dueThisMonthHint:
                'Ausstehende Zahlungen, die im aktuellen Monat fällig sind.',
            overdue: 'Überfällig',
            overdueHint:
                'Zahlungen, deren Fälligkeitsdatum überschritten ist und die Aufmerksamkeit benötigen.',
            recurringCommitments: 'Wiederkehrende Verpflichtungen',
            recurringCommitmentsHint:
                'Feste wiederkehrende Ausgaben auf Autopilot.',
            calendarTitle: 'Kalender',
            calendarSubtitle:
                'Monatliche Planungsansicht mit rechnungsbezogenen Erinnerungen.',
            invoices: 'Rechnungen',
            today: 'Heute',
            selectedDay: 'Ausgewählter Tag',
            createInvoiceForDay: 'Rechnung für diesen Tag erstellen',
            moreDayActions:
                'Weitere Tagesaktionen könnten in zukünftigen Updates verfügbar sein.',
            forecast: 'Prognose',
            upcomingPayments: 'Kommende Zahlungen',
            upcomingPaymentsDescription:
                'Monatlicher Abfluss, aufgeteilt in feste wiederkehrende und flexible einmalige Zahlungen. Klicken Sie auf einen Balken, um den Kalender zu diesem Monat zu springen.',
            months6: '6 Monate',
            months12: '12 Monate',
            upcoming: 'Kommend',
            recentUpcoming: 'Aktuelle & kommende Rechnungen',
            recentUpcomingDescription:
                'Nächste erwartete Zahlungstermine für aktive Rechnungen.',
            noData: 'Noch keine Daten',
            noDataHint:
                'Fügen Sie eine Rechnung hinzu, dann erscheinen kommende Zahlungen hier.',
        },
        invoices: {
            listTitle: 'Rechnungsliste',
            listSubtitle:
                'Eine einfache Übersicht über Ihre Rechnungen und ihren aktuellen Status.',
            searchPlaceholder: 'Rechnungen suchen...',
            all: 'Alle',
            oneTime: 'Einmalig',
            recurring: 'Wiederkehrend',
            soonest: 'Am ehesten',
            latest: 'Neueste',
            edit: 'Bearbeiten',
            delete: 'Löschen',
            summary: 'Zusammenfassung',
            insights: 'Rechnungsübersicht',
            insightsDescription:
                'Dieses Panel zeigt Ihre aktuellen Such-, Filter- und Sortierauswahlen.',
            visibleInvoices: 'Sichtbare Rechnungen',
            recurringShort: 'Wiederkehrend',
            oneTimeShort: 'Einmalig',
            item: 'Eintrag',
            items: 'Einträge',
            noData: 'Noch keine Daten',
            noResultsTitle: 'Keine Rechnungen entsprechen Ihren Filtern',
            noResultsDescription:
                'Versuchen Sie, die Suche zu löschen, den Typfilter zu wechseln oder die Sortierung zu ändern.',
            due: 'Fällig',
            id: 'ID',
            pending: 'Ausstehend',
            paid: 'Bezahlt',
            overdue: 'Überfällig',
            deleteInvoice: 'Rechnung löschen?',
            deleteWarning: 'Diese Aktion kann nicht rückgängig gemacht werden.',
            deleteConfirmation: 'Möchten Sie wirklich löschen',
            deleteConfirmationTail:
                'Diese Rechnung wird dauerhaft entfernt und kann nicht wiederhergestellt werden.',
            keepIt: 'Behalten',
            noDueDate: 'Kein Fälligkeitsdatum',
            title: 'Titel',
            preview: 'Vorschau',
            createInvoice: 'Rechnung erstellen',
            createInvoiceSubtitle:
                'Fügen Sie eine neue Rechnung hinzu, indem Sie die Details unten eingeben oder eine vorhandene PDF importieren.',
            manualEntry: 'Manuelle Eingabe',
            importPdf: 'PDF importieren',
            price: 'Preis',
            occurrencePrice: 'Preis je Vorkommen',
            type: 'Typ',
            status: 'Status',
            startDate: 'Startdatum',
            endDate: 'Enddatum',
            date: 'Datum',
            recurrence: 'Wiederkehr',
            invoicePdf: 'Rechnungs-PDF',
            choosePdf: 'PDF auswählen',
            pdfHint: 'PDF',
            recurringRangeError:
                'Das Enddatum der Wiederkehr muss nach oder gleich dem Startdatum sein.',
            choosePdfPrompt: 'Bitte wählen Sie eine PDF-Datei zum Importieren aus.',
            invoiceProcessing: 'Die Rechnung wird verarbeitet.',
            importFailed: 'Die Rechnung konnte nicht importiert werden.',
            invoiceImportPreview: 'Rechnung importieren',
            invoicePreview: 'Neue Rechnung',
            invoiceImportDescription:
                'Wählen Sie eine PDF aus und übergeben Sie sie dem Rechnungsleser.',
            invoiceManualDescription:
                'Das Formular passt sich automatisch für einmalige und wiederkehrende Rechnungen an.',
            typeLabel: 'Typ',
            statusLabel: 'Status',
            currencyLabel: 'Währung',
            recurringScheduleEndless: 'Wiederkehrender Zeitplan: Endlos',
            recurringScheduleUnset: 'Wiederkehrender Zeitplan: nicht gesetzt',
            saving: 'Speichern...',
            deleteInvoiceButton: 'Rechnung löschen',
            deleting: 'Löschen...',
            invoiceUpdated: 'Die Rechnung wurde erfolgreich aktualisiert.',
            invoiceCreated: 'Die Rechnung wurde erfolgreich erstellt.',
            invoiceDeleted: 'Die Rechnung wurde erfolgreich gelöscht.',
            editInvoice: 'Rechnung bearbeiten',
            editInvoiceSubtitle:
                'Aktualisieren Sie Rechnungsdetails, Status oder löschen Sie den Eintrag.',
            loadingInvoice: 'Rechnung wird geladen...',
            missingInvoiceId: 'Fehlende Rechnungs-ID.',
            failedToLoadInvoice: 'Die Rechnung konnte nicht geladen werden.',
            failedToDeleteInvoice: 'Die Rechnung konnte nicht gelöscht werden.',
            failedToUpdateInvoice: 'Die Rechnung konnte nicht aktualisiert werden.',
            failedToCreateInvoice: 'Die Rechnung konnte nicht erstellt werden.',
            importInvoice: 'Rechnung importieren',
            importing: 'Wird importiert...',
            saveNewInvoice: 'Rechnung erstellen',
            saveNewInvoiceLoading: 'Speichern...',
            previewType: 'Typ',
            previewStatus: 'Status',
            previewCurrency: 'Währung',
            previewSchedule: 'Wiederkehrender Zeitplan',
            previewPrice: 'Preis',
            previewSummary: 'Vorschau',
            dateLabel: 'Datum',
            titlePlaceholder: 'Abonnement',
            allInvoices: 'alle Rechnungen',
        },
        auth: {
            welcomeBack: 'Willkommen zurück',
            login: 'Anmelden',
            email: 'E-Mail',
            password: 'Passwort',
            rememberMe: 'Angemeldet bleiben',
            loggingIn: 'Anmeldung...',
            forgotPassword: 'Passwort vergessen?',
            needAccount: 'Noch kein Konto?',
            createOne: 'Erstellen',
            createAccount: 'Konto erstellen',
            register: 'Registrieren',
            registering: 'Konto wird erstellt...',
            confirmPassword: 'Passwort bestätigen',
            alreadyHaveAccount: 'Sie haben bereits ein Konto?',
            passwordReset: 'Passwort zurücksetzen',
            forgotPasswordTitle: 'Passwort vergessen',
            sendResetLink: 'Link senden',
            sendingResetLink: 'Wird gesendet...',
            rememberPassword: 'Erinnern Sie sich an Ihr Passwort?',
            backToLogin: 'Zurück zur Anmeldung',
            resetLinkSent:
                'Wenn dieses Konto existiert, wurde ein Link zum Zurücksetzen des Passworts gesendet.',
            resetLinkFailed:
                'Wir konnten den Link zum Zurücksetzen nicht senden. Bitte versuchen Sie es erneut.',
            secureAccess: 'Sicherer Zugriff',
            setNewPassword: 'Neues Passwort festlegen',
            newPassword: 'Neues Passwort',
            saveNewPassword: 'Passwort speichern',
            savingNewPassword: 'Wird gespeichert...',
            needNewResetLink: 'Benötigen Sie einen neuen Link?',
            requestAnother: 'Erneut anfragen',
            resetLinkInvalid:
                'Der Link zum Zurücksetzen ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.',
            sessionExpired:
                'Ihre Sitzung ist abgelaufen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.',
            somethingWentWrong:
                'Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut.',
        },
        profile: {
            settingsTitle: 'Profileinstellungen',
            settingsSubtitle:
                'Verwalten Sie Ihre Kontodetails, Einstellungen und Sicherheit.',
            account: 'Konto',
            preferences: 'Einstellungen',
            security: 'Sicherheit',
            dangerZone: 'Gefahrenzone',
            logout: 'Abmelden',
            profileCardTitle: 'Profil',
            profileCardSubtitle: 'Ihr Name und Ihre Anmelde-E-Mail-Adresse.',
            name: 'Name',
            email: 'E-Mail',
            saveChanges: 'Änderungen speichern',
            saving: 'Speichern...',
            displayCurrency: 'Anzeige-Währung',
            displayCurrencySubtitle:
                'Dashboard-Werte und die Prognose werden in dieser Währung umgerechnet.',
            preferredCurrency: 'Bevorzugte Währung',
            savePreferences: 'Einstellungen speichern',
            savePreferencesLoading: 'Speichern...',
            changePassword: 'Passwort ändern',
            changePasswordSubtitle:
                'Wählen Sie ein starkes Passwort, das Sie nirgendwo anders verwenden.',
            currentPassword: 'Aktuelles Passwort',
            newPassword: 'Neues Passwort',
            confirmNewPassword: 'Neues Passwort bestätigen',
            updatePassword: 'Passwort aktualisieren',
            updatingPassword: 'Aktualisieren...',
            deleteAccount: 'Konto löschen',
            deleteAccountSubtitle:
                'Löschen Sie Ihr Konto und alle zugehörigen Daten dauerhaft. Dies kann nicht rückgängig gemacht werden.',
            confirmPasswordDelete: 'Bestätigen Sie Ihr Passwort',
            deleteAccountButton: 'Konto löschen',
            deletingAccount: 'Löschen...',
            resendVerification: 'Verifizierungs-E-Mail erneut senden',
            sendingVerification: 'Senden...',
            emailUnverifiedTitle: 'Ihre E-Mail-Adresse wurde noch nicht verifiziert.',
            emailUnverifiedText:
                'Sie können die App weiterhin nutzen, aber die Verifizierung hilft, Ihr Konto sicher zu halten.',
            emailVerificationSent: 'Bestätigungs-E-Mail erneut gesendet.',
            emailVerificationFailed:
                'Wir konnten die E-Mail nicht erneut senden. Bitte versuchen Sie es erneut.',
            profileUpdated: 'Profil aktualisiert.',
            failedToUpdateProfile: 'Das Profil konnte nicht aktualisiert werden.',
            preferencesUpdated: 'Einstellungen aktualisiert.',
            failedToUpdatePreferences: 'Die Einstellungen konnten nicht aktualisiert werden.',
            passwordUpdated: 'Passwort aktualisiert.',
            failedToUpdatePassword: 'Das Passwort konnte nicht aktualisiert werden.',
            deleteAccountConfirm: 'Diese Aktion ist irreversibel. Konto löschen?',
            failedToDeleteAccount: 'Das Konto konnte nicht gelöscht werden.',
            networkError: 'Netzwerkfehler.',
        },
} as const;

function getBrowserLocale(): Locale {
    const browserLocale =
        navigator.languages?.[0] ?? navigator.language ?? 'en-US';

    return browserLocale.toLowerCase().startsWith('de') ? 'de' : 'en';
}

const translations = {
    en: enTranslations,
    de: deTranslations,
} as const;

const storedLocale =
    typeof window !== 'undefined'
        ? window.localStorage.getItem('app-locale')
        : null;

const initialLocale =
    storedLocale && supportedLocales.includes(storedLocale as Locale)
        ? (storedLocale as Locale)
        : getBrowserLocale();

export const locale = ref<Locale>(initialLocale);

watch(
    locale,
    (value) => {
        document.documentElement.lang = value;
        window.localStorage.setItem('app-locale', value);
    },
    { immediate: true },
);

export function setLocale(nextLocale: Locale) {
    locale.value = nextLocale;
}

export function t(key: string): string {
    const segments = key.split('.');
    const current = translations[locale.value];
    let value: unknown = current;

    for (const segment of segments) {
        if (typeof value === 'object' && value !== null && segment in value) {
            value = (value as Record<string, unknown>)[segment];
            continue;
        }

        const fallback = translations.en;
        let fallbackValue: unknown = fallback;

        for (const fallbackSegment of segments) {
            if (
                typeof fallbackValue === 'object' &&
                fallbackValue !== null &&
                fallbackSegment in fallbackValue
            ) {
                fallbackValue = (fallbackValue as Record<string, unknown>)[
                    fallbackSegment
                ];
                continue;
            }

            return key;
        }

        return typeof fallbackValue === 'string' ? fallbackValue : key;
    }

    return typeof value === 'string' ? value : key;
}
