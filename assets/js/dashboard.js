// FILE: assets/js/dashboard.js
// Replace the entire file to ensure all functions are up to date.

document.addEventListener('DOMContentLoaded', () => {

    // --- GLOBAL STATE ---
    let currentRecordId = null;

    // --- DOM ELEMENT SELECTORS ---
    const kpi = {
        total: document.getElementById('kpi-total'),
        passRate: document.getElementById('kpi-pass-rate'),
        failRate: document.getElementById('kpi-fail-rate'),
    };
    const recentInspectionsTbody = document.getElementById('recent-inspections-tbody');
    const modal = document.getElementById('inspection-modal');
    const inspectionForm = document.getElementById('inspection-form');
    const steps = {
        step1: document.getElementById('step-1'),
        step2: document.getElementById('step-2'),
        step3: document.getElementById('step-3'),
    };
    const resultsContainer = document.getElementById('evaluation-results-container');
    const pdfIframe = document.getElementById('pdf-iframe');

    // --- ASYNC DATA FETCHER ---
    const fetchData = async (url, options = {}) => {
        try {
            const response = await fetch(url, options);
            const responseText = await response.text();
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}, message: ${responseText}`);
            }
            return JSON.parse(responseText);
        } catch (error) {
            console.error("Fetch Error:", error);
            Swal.fire('Error', `An error occurred. Check the console for details.`, 'error');
            return null;
        }
    };

    // --- UI UPDATE FUNCTIONS ---
    const updateKpis = (data) => {
        kpi.total.textContent = data.total_inspections;
        kpi.passRate.textContent = `${data.pass_rate}%`;
        kpi.failRate.textContent = `${data.fail_rate}%`;
    };

    const updateRecentInspections = (inspections) => {
        recentInspectionsTbody.innerHTML = '';
        if (inspections.length === 0) {
            recentInspectionsTbody.innerHTML = `<tr><td colspan="6">No inspections recorded today.</td></tr>`;
            return;
        }
        inspections.forEach(record => {
            const statusClass = record.evaluation_status.toLowerCase();
            const row = `
                <tr>
                    <td>${record.component_name}</td>
                    <td>${record.batch_number}</td>
                    <td><span class="status-badge ${statusClass}">${record.evaluation_status}</span></td>
                    <td>${new Date(record.timestamp).toLocaleString()}</td>
                    <td>${record.full_name}</td>
                    <td class="actions">
                        <button class="btn-icon" data-report-path="${record.report_path}" title="View Report">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
            recentInspectionsTbody.insertAdjacentHTML('beforeend', row);
        });
    };

    // --- CORE DASHBOARD LOGIC ---
    const refreshDashboardData = async () => {
        const data = await fetchData('api/get_dashboard_data.php');
        if (data && data.success) {
            updateKpis(data.kpis);
            updateRecentInspections(data.recent_inspections);
        }
    };

    // --- MODAL MANAGEMENT ---
    const showModal = () => {
        gsap.to(modal, { duration: 0.3, autoAlpha: 1 });
        gsap.fromTo('.modal-content', { y: -50, opacity: 0 }, { duration: 0.4, y: 0, opacity: 1, delay: 0.1, ease: 'power2.out' });
    };

    const hideModal = () => {
        gsap.to('.modal-content', { duration: 0.3, y: 50, opacity: 0, ease: 'power2.in' });
        gsap.to(modal, { duration: 0.4, autoAlpha: 0, delay: 0.1 });
        resetModal();
    };

    const switchStep = (fromStep, toStep) => {
        gsap.to(fromStep, { duration: 0.3, scale: 0.95, opacity: 0, display: 'none', ease: 'power2.in' });
        gsap.fromTo(toStep, 
            { scale: 1.05, opacity: 0, display: 'block' },
            { duration: 0.3, scale: 1, opacity: 1, ease: 'power2.out', delay: 0.3 }
        );
        Object.values(steps).forEach(s => s.classList.remove('active'));
        toStep.classList.add('active');
    };
    
    const resetModal = () => {
        inspectionForm.reset();
        currentRecordId = null;
        pdfIframe.src = 'about:blank';
        Object.values(steps).forEach((step, index) => {
            step.style.display = index === 0 ? 'block' : 'none';
            step.style.opacity = index === 0 ? 1 : 0;
            step.classList.toggle('active', index === 0);
        });
    };

    // --- EVENT LISTENERS ---
    document.getElementById('start-inspection-btn').addEventListener('click', showModal);
    document.getElementById('close-modal-btn-1').addEventListener('click', hideModal);
    document.getElementById('close-modal-btn-3').addEventListener('click', hideModal);
    document.getElementById('back-to-step-1-btn').addEventListener('click', () => switchStep(steps.step2, steps.step1));

    // Form Submission (Step 1 -> Step 2)
    inspectionForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = inspectionForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Evaluating...`;

        const result = await fetchData('api/submit_inspection.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(new FormData(inspectionForm))),
        });

        submitButton.disabled = false;
        submitButton.innerHTML = `Evaluate Results <i class="fas fa-arrow-right"></i>`;

        if (result && result.success) {
            currentRecordId = result.record_id;
            // The PHP now sends the complete HTML, including the AI suggestion
            resultsContainer.innerHTML = result.evaluation_html;
            switchStep(steps.step1, steps.step2);
        }
        // No else needed, fetchData handles the Swal error popup.
    });

    // Generate PDF (Step 2 -> Step 3)
    document.getElementById('generate-pdf-btn').addEventListener('click', async (e) => {
        const button = e.currentTarget;
        button.disabled = true;
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Generating...`;
        
        const result = await fetchData(`api/get_dashboard_data.php?record_id=${currentRecordId}`);
        
        button.disabled = false;
        button.innerHTML = `Generate Report <i class="fas fa-file-pdf"></i>`;

        if(result && result.success && result.report_path) {
            pdfIframe.src = result.report_path + `?t=${new Date().getTime()}`;
            switchStep(steps.step2, steps.step3);
        } else {
            Swal.fire('Error', 'Could not find the generated report.', 'error');
        }
    });

    // Send Email (Step 3 -> Close)
    document.getElementById('send-email-btn').addEventListener('click', async (e) => {
        const button = e.currentTarget;
        button.disabled = true;
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Sending...`;
        
        const result = await fetchData('api/send_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ record_id: currentRecordId })
        });
        
        button.disabled = false;
        button.innerHTML = `Confirm & Send Email <i class="fas fa-paper-plane"></i>`;

        if (result && result.success) {
            Swal.fire('Success!', result.message, 'success');
            hideModal();
            refreshDashboardData();
        }
    });
    
    // View Report from Table
    recentInspectionsTbody.addEventListener('click', (e) => {
        const viewButton = e.target.closest('button[data-report-path]');
        if (viewButton) {
            const reportPath = viewButton.dataset.reportPath;
            if (reportPath && reportPath !== 'null') {
                 Swal.fire({
                    title: 'Report Preview',
                    html: `<iframe src="${reportPath}?t=${new Date().getTime()}" style="width:100%; height:70vh; border:none;"></iframe>`,
                    width: '80%',
                    showConfirmButton: true,
                    confirmButtonText: 'Close'
                });
            } else {
                Swal.fire('Info', 'No report is available for this record.', 'info');
            }
        }
    });

    // --- INITIALIZATION ---
    refreshDashboardData();
    setInterval(refreshDashboardData, 30000);
});