                </div> <!-- End tab content -->
            </div> <!-- End col -->
        </div> <!-- End row -->
    </div> <!-- End container -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple script to populate update form when guest is selected
        document.getElementById('update_guest_id').addEventListener('change', function() {
            const guestId = this.value;
            if (guestId) {
                fetch('get_guest.php?id=' + guestId)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('update_firstname').value = data.firstname;
                        document.getElementById('update_lastname').value = data.lastname;
                        document.getElementById('update_email').value = data.email || '';
                    });
            }
        });
    </script>
</body>
</html>