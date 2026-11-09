# Deployment acceptance on disposable CI

This suite complements the 21 browser tests. It uses Docker Compose and real PHP/MariaDB; it does not deploy to an external development server.

`backend/tests/deployment.py` refuses to run unless `CI=true`. It mutates test data, removes an upload probe, restores a backup and removes/recreates containers. Never run it on a live server or against real institutional data.

The workflow generates a one-day self-signed test certificate for localhost. Python trusts that specific certificate and verifies the hostname; it also confirms the certificate is rejected without that trust anchor. The test never disables TLS verification. The CI proxy redirects HTTP to HTTPS, sends HSTS and enables TLS 1.2/1.3. The application uses Secure, HttpOnly and SameSite Strict session cookies.

Checks cover session/room/private-attachment persistence after DB/PHP/web restart; the actual scheduler service generating due work, a second loop and restart without duplicates; all-table content hashes and all upload-file hashes after a deliberately damaged test state is restored; and retained volumes after container removal/recreation. PHP is also force-recreated while the existing proxies keep running. Published web/TLS ports must bind to loopback, and DB/PHP must have no published ports.

The CI-only override `docker/compose.ci-tls.yaml` and proxy `docker/ci/tls.conf` are test fixtures. For a real server, use its domain and certificate, a trusted reverse proxy, new secrets and a separate test database. Do not install the self-signed CI certificate for production.

Passing this test establishes behavior for the tested Compose stack. It does not establish external DNS, the server's own firewall/certificate configuration, long-term scheduler operation, real-device compatibility or institutional UAT approval. Those require the target environment.

## Fix found during acceptance

Stopping/starting the web service during restore changed its container IP. The TLS proxy retained its previous DNS answer and returned 502 after the database and files were restored. Both the Docker web/FastCGI proxy and CI TLS proxy now resolve service names through Docker DNS (`127.0.0.11`) with a five-second validity period. This resolver is for the Docker network; a native host Nginx configuration must use the host environment’s resolver/backend address.

## Verified result

[Run #7](https://github.com/nnkmz/RuangUiTM/actions/runs/42576424220), commit `e38ec6100826137b8f9cb4d00a519b0f04acabc6`: **completed / success** on 9 November 2026. All 22 deployment checks passed, including content hashes for 27 tables and 5 upload files after damaged-state recovery. The same run passed 214 backend assertions, 33 frontend tests, 21 browser tests, production builds and cleanup. Machine-readable evidence is retained in `DEPLOYMENT_RESULTS.json` and the CI artifact `deployment-evidence`.
