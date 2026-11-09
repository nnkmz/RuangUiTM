# Deployment acceptance on disposable CI

This suite complements the 21 browser tests. It uses Docker Compose and real PHP/MariaDB; it does not deploy to an external development server.

`backend/tests/deployment.py` refuses to run unless `CI=true`. It mutates test data, removes an upload probe, restores a backup and removes/recreates containers. Never run it on a live server or against real institutional data.

The workflow generates a one-day self-signed test certificate for localhost. Python trusts that specific certificate and verifies the hostname; it also confirms the certificate is rejected without that trust anchor. The test never disables TLS verification. The CI proxy redirects HTTP to HTTPS, sends HSTS and enables TLS 1.2/1.3. The application uses Secure, HttpOnly and SameSite Strict session cookies.

Checks cover session/room/private-attachment persistence after DB/PHP/web restart; the actual scheduler service generating due work, a second loop and restart without duplicates; all-table content hashes and all upload-file hashes after a deliberately damaged test state is restored; and retained volumes after container removal/recreation. Published web/TLS ports must bind to loopback, and DB/PHP must have no published ports.

The CI-only override `docker/compose.ci-tls.yaml` and proxy `docker/ci/tls.conf` are test fixtures. For a real server, use its domain and certificate, a trusted reverse proxy, new secrets and a separate test database. Do not install the self-signed CI certificate for production.

Passing this test establishes behavior for the tested Compose stack. It does not establish external DNS, the server's own firewall/certificate configuration, long-term scheduler operation, real-device compatibility or institutional UAT approval. Those require the target environment.

Result: pending first workflow execution.
