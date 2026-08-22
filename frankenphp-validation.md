> ⚠️ **Legacy doc:** written for the Symfony version of this demo. The commands and endpoints may not match the Laravel branch yet.

# FrankenPHP Configuration Validation Guide

## Overview

FrankenPHP includes built-in configuration validation to ensure your Caddyfile is correctly formatted and configured before deploying. This guide covers how to validate your configuration, interpret the output, and fix common issues.

## Quick Start

### Validating Configuration

To validate your FrankenPHP configuration inside the container:

```bash
docker compose exec -t franken bash
frankenphp validate --config=/etc/frankenphp/Caddyfile
```

For the worker mode container:

```bash
docker compose exec -t franken-worker bash
frankenphp validate --config=/etc/frankenphp/Caddyfile
```

## Understanding Validation Output

### Successful Validation

When your configuration is valid, you'll see output similar to:

```
2025/11/24 09:47:06.518    INFO    using config from file    {"file": "/etc/frankenphp/Caddyfile"}
2025/11/24 09:47:06.519    INFO    adapted config to JSON    {"adapter": "caddyfile"}
2025/11/24 09:47:06.519    INFO    http.auto_https    server is listening only on the HTTPS port but has no TLS connection policies; adding one to enable TLS    {"server_name": "srv0", "https_port": 443}
2025/11/24 09:47:06.519    INFO    http.auto_https    enabling automatic HTTP->HTTPS redirects    {"server_name": "srv0"}
2025/11/24 09:47:06.520    INFO    http    servers shutting down with eternal grace period
Valid configuration
```

The final line `Valid configuration` confirms your Caddyfile is syntactically correct and semantically valid.

### Validation Messages

#### INFO Messages

INFO messages provide informational feedback about your configuration:

- **`using config from file`**: Confirms the config file location
- **`adapted config to JSON`**: Caddyfile successfully converted to Caddy's internal JSON format
- **`server is listening only on the HTTPS port`**: Caddy detected HTTPS configuration and automatically adds TLS
- **`enabling automatic HTTP->HTTPS redirects`**: Automatic redirects from HTTP to HTTPS are enabled

These messages are normal and do not require action.

#### WARN Messages

WARN messages indicate potential issues that don't prevent the config from working:

```
2025/11/24 09:47:06.519    WARN    Caddyfile input is not formatted; run 'caddy fmt --overwrite' to fix inconsistencies    {"adapter": "caddyfile", "file": "/etc/frankenphp/Caddyfile", "line": 12}
```

Common warnings:

1. **Formatting Inconsistencies**: Indentation or spacing issues
2. **Deprecated Directives**: Using older configuration syntax
3. **Missing Recommended Options**: Configuration works but could be improved

#### ERROR Messages

ERROR messages indicate problems that will prevent FrankenPHP from starting:

```
Error: adapting config using caddyfile: parsing caddyfile tokens for 'frankenphp': "max_threads" must be greater than or equal to "num_threads"
```

The configuration will **not** be valid until errors are resolved.

## Fixing Common Issues

### Issue 1: Formatting Inconsistencies

**Warning Message:**
```
WARN    Caddyfile input is not formatted; run 'caddy fmt --overwrite' to fix inconsistencies
```

**How to Fix:**

Inside the container, format the Caddyfile:

```bash
docker compose exec -t franken bash
frankenphp fmt --overwrite /etc/frankenphp/Caddyfile
```

now let's check out configuration and ensure formatting is not show up

### Issue 2: Thread Configuration Errors

**Error Message:**
```
Error: "max_threads" must be greater than or equal to "num_threads"
```

**How to Fix:**

When we trying to modify /etc/frankenphp/Caddyfile.regular like below:

```caddyfile
# Before (invalid)
frankenphp {
	num_threads 16
	max_threads 8    # ERROR: Less than num_threads
}
```

it will invalid, if we run reload on frankenphp config

```bash
frankenphp reload --config=/etc/frankenphp/Caddyfile

# Error will show this
# 2025/11/24 09:55:30.509	INFO	using config from file	{"file": "/etc/frankenphp/Caddyfile"}
# Error: adapting config using caddyfile: parsing caddyfile tokens for 'frankenphp': "max_threads"" must be greater than or equal to "num_threads"
```

ensure `max_threads` >= `num_threads`

how to fix?

```
# After (valid)
frankenphp {
	num_threads 16
	max_threads 32   # OK: Greater than num_threads
	# or
	max_threads auto # OK: Auto-scaling
}
```

and run format

```bash
frankenphp fmt --overwire --config=/etc/frankenphp/Caddyfile
```

and then reload

```bash
frankenphp reload --config=/etc/frankenphp/Caddyfile
# 2025/11/24 09:59:47.709	INFO	using config from file	{"file": "/etc/frankenphp/Caddyfile"}
# 2025/11/24 09:59:47.710	INFO	adapted config to JSON	{"adapter": "caddyfile"}
```

now notice that our configuration free-errors

### Issue 3: Worker Configuration Errors

**Error Message:**
```
Error: "num_threads" must be greater than worker "num" directive
```

**How to Fix:**

Ensure the number of threads is greater than the number of workers:

```caddyfile
# Before (invalid)
frankenphp {
	num_threads 4
	worker {
		file ./public/index.php
		num 8    # ERROR: More workers than threads
	}
}

# After (valid)
frankenphp {
	num_threads 16
	worker {
		file ./public/index.php
		num 8    # OK: Threads > Workers
	}
}
```

### Issue 4: Missing Required Directives

**Error Message:**
```
Error: parsing caddyfile tokens for 'frankenphp': missing required 'file' directive in worker block
```

**How to Fix:**

Ensure worker blocks include the required `file` directive:

```caddyfile
# Before (invalid)
frankenphp {
	worker {
		num 8
	}
}

# After (valid)
frankenphp {
	worker {
		file ./public/index.php  # Required
		num 8
	}
}
```

### Issue 5: Invalid Path References

**Error Message:**
```
Error: worker file does not exist: /invalid/path/index.php
```

**How to Fix:**

Verify the worker file path exists inside the container:

```bash
docker compose exec -t franken bash
ls -la /var/www/html/public/index.php
```

Update the Caddyfile with the correct path:

```caddyfile
frankenphp {
	worker {
		file ./public/index.php        # Relative to SERVER_ROOT
		# or
		file /var/www/html/public/index.php  # Absolute path
	}
}
```

## Validation Workflow

### During Development

1. **Edit the Caddyfile**:
   ```bash
   vim docker/Caddyfile
   ```

2. **Validate the configuration**:
   ```bash
   docker compose exec -t franken bash
   frankenphp validate --config=/etc/frankenphp/Caddyfile
   ```

3. **Fix any errors or warnings**

4. **Restart the container**:
   ```bash
   docker compose restart franken
   # or for worker mode
   docker compose restart franken-worker
   ```

5. **Verify the service is running**:
   ```bash
   docker compose logs franken
   ```


## Advanced Validation

### Validating Without Starting the Server

Check configuration syntax without starting FrankenPHP:

```bash
docker compose exec -t franken bash
frankenphp validate --config=/etc/frankenphp/Caddyfile --adapter caddyfile
```

### Viewing the Adapted JSON Configuration

See how Caddy interprets your Caddyfile:

```bash
docker compose exec -t franken bash
frankenphp adapt --config=/etc/frankenphp/Caddyfile --adapter caddyfile | jq
```

This outputs the JSON representation of your configuration, useful for debugging complex setups.

### Testing Configuration Changes Live

FrankenPHP supports reloading configuration without downtime:

```bash
docker compose exec -t franken bash
frankenphp reload --config=/etc/frankenphp/Caddyfile
```

If the new configuration is invalid, the reload will fail and the old configuration remains active.

## Best Practices

### 1. Use Caddy Format Tool

Keep your Caddyfile formatted consistently:

```bash
caddy fmt --overwrite docker/Caddyfile
```

### 2. Document Configuration Decisions

Add comments to your Caddyfile:

```caddyfile
{
	admin 0.0.0.0:2019  # Exposed for monitoring (use localhost in production)
	metrics             # Enable Prometheus metrics
}
```

### 3. Test Configuration in Staging

Always validate configuration changes in a non-production environment first.

### 4. Monitor Logs After Changes

After deploying configuration changes:

```bash
docker compose logs -f franken
docker compose logs -f franken-worker
```

Watch for startup errors or warnings.

### Production-Ready Configuration

```caddyfile
{
	admin localhost:2019  # Restricted to localhost
	metrics

	frankenphp {
		num_threads 32
		worker {
			file /var/www/html/public/index.php
			num 24
			# watch disabled for production
		}
		php_ini {
			memory_limit 512M
			opcache.memory_consumption 256
			opcache.validate_timestamps 0
		}
	}
}

{$SERVER_NAME:localhost} {
	root {$SERVER_ROOT:/var/www/html/public}
	encode zstd br gzip

	header {
		X-Frame-Options DENY
		X-Content-Type-Options nosniff
		Strict-Transport-Security "max-age=31536000;"
	}

	php_server
}
```

### Development Configuration with Auto-Reload

```caddyfile
{
	admin 0.0.0.0:2019
	metrics
	debug

	frankenphp {
		num_threads 8
		worker {
			file ./public/index.php
			num 4
			watch  # Enable auto-reload
		}
		php_ini memory_limit 512M
	}
}

localhost:8080 {
	root ./public
	encode gzip
	php_server
}
```

## Validation Checklist

Before deploying configuration changes, verify:

- [ ] Configuration validates successfully
- [ ] No ERROR messages in validation output
- [ ] WARN messages reviewed and addressed
- [ ] `num_threads` >= `worker.num`
- [ ] `max_threads` >= `num_threads` (if specified)
- [ ] Worker `file` directive points to existing PHP file
- [ ] Memory calculations verified: `num_threads × memory_limit < available_memory`
- [ ] `watch` directive disabled for production
- [ ] Admin API restricted to localhost in production
- [ ] Configuration formatted with `caddy fmt`
- [ ] Changes tested in staging environment
- [ ] Logs monitored after deployment

## Additional Resources

- **FrankenPHP Documentation**: https://frankenphp.dev/docs/
- **Caddy Documentation**: https://caddyserver.com/docs/
- **Project FrankenPHP Guide**: [frankenphp.md](./frankenphp.md)
- **Performance Testing**: [frankenphp.md#performance-testing--monitoring](./frankenphp.md#performance-testing--monitoring)

## Quick Reference

### Common Commands

```bash
# Validate configuration
frankenphp validate --config=/etc/frankenphp/Caddyfile

# Format Caddyfile
caddy fmt --overwrite /etc/frankenphp/Caddyfile

# View JSON configuration
frankenphp adapt --config=/etc/frankenphp/Caddyfile --adapter caddyfile

# Reload configuration (no downtime)
frankenphp reload --config=/etc/frankenphp/Caddyfile

# Test PHP syntax
php -l /var/www/html/public/index.php
```