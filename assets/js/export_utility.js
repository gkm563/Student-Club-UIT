/**
 * Unified Export Engine for ClubHub UIT
 * Supports CSV, Excel (.xlsx/.xls), and Styled PDF Printing for all tables.
 */

window.ClubHubExporter = {
    /**
     * Clean cell text for tabular export
     */
    cleanText: function(text) {
        if (!text) return '';
        return text.trim()
            .replace(/\s+/g, ' ')
            .replace(/"/g, '""');
    },

    /**
     * Export HTML Table to CSV (.csv) with UTF-8 BOM for Microsoft Excel compatibility
     */
    exportCSV: function(tableId, filename) {
        const table = document.getElementById(tableId);
        if (!table) { alert('Table not found: ' + tableId); return; }

        let csvLines = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            if (row.style.display === 'none') return; // Skip hidden rows from search filters
            
            const cols = row.querySelectorAll('th, td');
            let rowData = [];
            
            cols.forEach((col, index) => {
                // Skip action buttons column if last column contains buttons/links
                if (col.classList.contains('text-end') && index === cols.length - 1 && col.querySelector('.btn-group, .btn')) {
                    return;
                }
                let cellText = this.cleanText(col.innerText);
                rowData.push('"' + cellText + '"');
            });

            if (rowData.length > 0) {
                csvLines.push(rowData.join(','));
            }
        });

        // Add UTF-8 BOM (\uFEFF) so Excel opens Hindi/Special chars correctly
        const csvContent = '\uFEFF' + csvLines.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = (filename || 'export-data') + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },

    /**
     * Export HTML Table to Excel (.xls) file
     */
    exportExcel: function(tableId, filename) {
        const table = document.getElementById(tableId);
        if (!table) { alert('Table not found: ' + tableId); return; }

        // Clone table to remove action columns before exporting
        const clone = table.cloneNode(true);
        clone.querySelectorAll('.text-end, th:last-child, td:last-child').forEach(el => {
            if (el.querySelector('.btn-group, .btn') || el.innerText.includes('ACTIONS')) {
                el.remove();
            }
        });

        const template = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="UTF-8">
                <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
                <x:Name>Report</x:Name>
                <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
                <style>
                    table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                    th { background-color: #4f46e5; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 10px; }
                    td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
                </style>
            </head>
            <body>
                <h2>ClubHub UIT - Official Data Export</h2>
                <p>Generated on: ${new Date().toLocaleString()}</p>
                ${clone.outerHTML}
            </body>
            </html>
        `;

        const blob = new Blob([template], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = (filename || 'export-data') + '-' + new Date().toISOString().slice(0, 10) + '.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },

    /**
     * Print / Save as PDF Report
     */
    exportPDF: function(tableId, title) {
        const table = document.getElementById(tableId);
        if (!table) { alert('Table not found: ' + tableId); return; }

        const printWin = window.open('', '_blank', 'width=900,height=700');
        const clone = table.cloneNode(true);
        
        // Remove Action buttons column
        clone.querySelectorAll('.text-end, th:last-child, td:last-child').forEach(el => {
            if (el.querySelector('.btn-group, .btn') || el.innerText.includes('ACTIONS')) {
                el.remove();
            }
        });

        printWin.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title || 'ClubHub UIT Report'}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: 'Inter', sans-serif; padding: 30px; color: #1e293b; }
                    .report-header { border-bottom: 2px solid #4f46e5; padding-bottom: 15px; margin-bottom: 25px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th { background-color: #f1f5f9 !important; color: #334155; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
                    th, td { border: 1px solid #e2e8f0; padding: 10px 12px; font-size: 0.85rem; }
                    .badge { font-size: 0.75rem; }
                    @media print {
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="report-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold text-dark mb-1">ClubHub UIT - Official Report</h3>
                        <p class="text-secondary small mb-0">${title || 'Administrative Data Overview'} • Dean of Student Affairs Office</p>
                    </div>
                    <div class="text-end small text-muted">
                        <div>Report Date: ${new Date().toLocaleDateString()}</div>
                        <div>Generated By: SAC Administration</div>
                    </div>
                </div>

                <div class="mb-3 no-print text-end">
                    <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Print / Save as PDF</button>
                    <button onclick="window.close()" class="btn btn-light btn-sm rounded-pill px-3">Close</button>
                </div>

                ${clone.outerHTML}

                <div class="mt-4 pt-3 border-top text-muted small text-center">
                    Confidential Report • United Institute of Technology (UIT) Student Activity Council
                </div>
            </body>
            </html>
        `);
        printWin.document.close();
    }
};
