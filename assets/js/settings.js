// FILE: assets/js/settings.js

document.addEventListener('DOMContentLoaded', () => {
    const rulesForm = document.getElementById('rules-form');
    if (!rulesForm) return; // Don't run if the user doesn't have permission

    // --- FETCH AND POPULATE CURRENT RULES ---
    const loadRules = async () => {
        try {
            const response = await fetch('api/get_settings.php');
            const result = await response.json();
            if (result.success) {
                result.rules.forEach(rule => {
                    if (rule.parameter_name === 'Temperature') {
                        document.getElementById('temp-min').value = rule.min_value;
                        document.getElementById('temp-moderate').value = rule.moderate_value;
                        document.getElementById('temp-max').value = rule.max_value;
                    } else if (rule.parameter_name === 'Pressure') {
                        document.getElementById('pressure-min').value = rule.min_value;
                        document.getElementById('pressure-moderate').value = rule.moderate_value;
                        document.getElementById('pressure-max').value = rule.max_value;
                    }
                });
            } else {
                Swal.fire('Error', 'Could not load current settings.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'An unexpected error occurred while loading settings.', 'error');
        }
    };
    
    // --- FORM SUBMISSION ---
    rulesForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = rulesForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Saving...`;

        const formData = new FormData(rulesForm);
        const data = Object.fromEntries(formData.entries());

        try {
             const response = await fetch('api/update_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire('Success!', 'Evaluation rules have been updated.', 'success');
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'An unexpected error occurred.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="fas fa-save"></i> Save Changes`;
        }
    });

    // --- INITIAL LOAD ---
    loadRules();
});