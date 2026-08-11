export const calculateOccurrencesCount = (startDate: string, endDate: string, recurrence: string): number => {
    if (!startDate || !endDate || !recurrence) return 0;

    const start = new Date(startDate);
    const end = new Date(endDate);

    if (end < start) return 0;

    let occurrences = 0;
    let current = new Date(start);

    while (current <= end) {
        occurrences++;
        switch (recurrence) {
            case 'weekly':
                current.setDate(current.getDate() + 7);
                break;
            case 'biweekly':
                current.setDate(current.getDate() + 14);
                break;
            case 'monthly':
                current.setMonth(current.getMonth() + 1);
                break;
            case 'quarterly':
                current.setMonth(current.getMonth() + 3);
                break;
            case 'semiannual':
                current.setMonth(current.getMonth() + 6);
                break;
            case 'yearly':
                current.setFullYear(current.getFullYear() + 1);
                break;
        }
    }

    return occurrences;
};