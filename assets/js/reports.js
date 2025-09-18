// FILE: assets/js/reports.js

document.addEventListener('DOMContentLoaded', () => {
    // --- DOM ELEMENTS ---
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const generateBtn = document.getElementById('generate-report-btn');
    const reportContent = document.getElementById('report-content');
    const reportsTableBody = document.querySelector('#reports-table tbody');
    const exportCsvBtn = document.getElementById('export-csv-btn');

    // --- CHART.JS INSTANCES ---
    let dailyVolumeChart = null;
    let statusRatioChart = null;
    let tableData = []; // Store current table data for export

    // --- INITIAL DATE SETUP ---
    const today = new Date().toISOString().split('T')[0];
    const sevenDaysAgo = new Date(Date.now() - 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    startDateInput.value = sevenDaysAgo;
    endDateInput.value = today;

    // --- CHART CREATION FUNCTIONS ---
    const createDailyVolumeChart = (labels, passData, failData) => {
        const ctx = document.getElementById('dailyVolumeChart').getContext('2d');
        if (dailyVolumeChart) dailyVolumeChart.destroy();
        dailyVolumeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Pass', data: passData, backgroundColor: 'rgba(40, 167, 69, 0.7)' },
                    { label: 'Fail', data: failData, backgroundColor: 'rgba(220, 53, 69, 0.7)' }
                ]
            },
            options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
        });
    };

    const createStatusRatioChart = (passCount, failCount) => {
        const ctx = document.getElementById('statusRatioChart').getContext('2d');
        if (statusRatioChart) statusRatioChart.destroy();
        statusRatioChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pass', 'Fail'],
                datasets: [{
                    data: [passCount, failCount],
                    backgroundColor: ['rgba(40, 167, 69, 0.9)', 'rgba(220, 53, 69, 0.9)'],
                    borderColor: ['#fff']
                }]
            },
            options: { responsive: true, cutout: '70%' }
        });
    };

    // --- DATA HANDLING ---
    const populateTable = (records) => {
        reportsTableBody.innerHTML = '';
        tableData = records; // Save for export
        if (records.length === 0) {
            reportsTableBody.innerHTML = `<tr><td colspan="7">No records found for the selected date range.</td></tr>`;
            return;
        }
        records.forEach(r => {
            const row = `
                <tr>
                    <td>${r.component_name}</td>
                    <td>${r.batch_number}</td>
                    <td><span class="status-badge ${r.evaluation_status.toLowerCase()}">${r.evaluation_status}</span></td>
                    <td>${new Date(r.timestamp).toLocaleString()}</td>
                    <td>${r.full_name}</td>
                    <td>${r.temperature}</td>
                    <td>${r.pressure}</td>
                </tr>
            `;
            reportsTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // --- MAIN REPORT GENERATION FUNCTION ---
    const generateReport = async () => {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) {
            Swal.fire('Warning', 'Please select both a start and end date.', 'warning');
            return;
        }

        generateBtn.disabled = true;
        generateBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Generating...`;

        try {
            const response = await fetch(`api/get_report_data.php?start_date=${startDate}&end_date=${endDate}`);
            const result = await response.json();
            
            if (result.success) {
                createDailyVolumeChart(result.charts.daily.labels, result.charts.daily.pass, result.charts.daily.fail);
                createStatusRatioChart(result.charts.ratio.pass, result.charts.ratio.fail);
                populateTable(result.table_data);
                reportContent.classList.remove('hidden');
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'An unexpected error occurred.', 'error');
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerHTML = `<i class="fas fa-sync-alt"></i> Generate Report`;
        }
    };
    
    // --- CSV EXPORT ---
    const exportToCsv = () => {
        if (tableData.length === 0) {
            Swal.fire('Info', 'There is no data to export.', 'info');
            return;
        }
        const headers = "Component,Batch,Status,Timestamp,Operator,Temperature,Pressure\n";
        const rows = tableData.map(r => 
            `"${r.component_name}","${r.batch_number}","${r.evaluation_status}","${new Date(r.timestamp).toLocaleString()}","${r.full_name}",${r.temperature},${r.pressure}`
        ).join("\n");
        
        const csvContent = headers + rows;
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8,' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", `qc_report_${startDateInput.value}_to_${endDateInput.value}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // --- EVENT LISTENERS ---
    generateBtn.addEventListener('click', generateReport);
    exportCsvBtn.addEventListener('click', exportToCsv);

    // --- INITIAL LOAD ---
    generateReport();
});