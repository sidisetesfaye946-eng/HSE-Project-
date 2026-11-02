<script>
    function employeeHistoryMixin() {
        const historyUrlTemplate = @json($employeeHistoryUrlTemplate);

        return {
            historyUrlTemplate,
            historyModalOpen: false,
            historyLoading: false,
            historyEmployee: null,
            historyAssignments: [],
            openHistoryModal(employeeId) {
                this.historyModalOpen = true;
                this.historyLoading = true;
                this.historyEmployee = null;
                this.historyAssignments = [];
                document.body.classList.add('overflow-y-hidden');

                const url = this.historyUrlTemplate.replace('__EMPLOYEE__', employeeId);

                fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => {
                        if (! response.ok) {
                            throw new Error('Failed to load');
                        }

                        return response.json();
                    })
                    .then((data) => {
                        this.historyEmployee = data.employee;
                        this.historyAssignments = data.assignments;
                    })
                    .catch(() => {
                        this.historyEmployee = { name: '{{ __('Unable to load history') }}', job_title: '' };
                        this.historyAssignments = [];
                    })
                    .finally(() => {
                        this.historyLoading = false;
                    });
            },
            closeHistoryModal() {
                this.historyModalOpen = false;
                document.body.classList.remove('overflow-y-hidden');
            },
        };
    }
</script>
