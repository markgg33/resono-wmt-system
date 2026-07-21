# Time Security Fix - Server Time Implementation

## Problem Statement
The WMT system was previously capturing task start and end times from the user's PC (client-side), which posed a security risk allowing users to manipulate task logs by changing their system time.

## Solution Overview
All time capturing has been migrated to use **server time** exclusively, with validation mechanisms to detect and prevent time tampering attempts.

---

## Changes Made

### 1. Frontend Changes - `js/start-tag-task.js`

#### Added New Function: `getServerTime()`
```javascript
async function getServerTime() {
  try {
    const res = await fetch("../backend/get_server_time.php");
    const data = await res.json();
    return new Date(data.server_time);
  } catch (err) {
    console.error("Failed to fetch server time:", err);
    alert("Failed to fetch server time. Using local time as fallback.");
    return new Date();
  }
}
```

#### Updated `startTask()` Function
- **Before**: Used `const now = new Date()` (client-side time)
- **After**: Uses `const now = await getServerTime()` (server-side time)
- **Impact**: All task start times are now captured from the server

### Key Changes in `startTask()`:
```javascript
// 🔐 CRITICAL: Fetch server time instead of using client-side time
const now = await getServerTime();
const startTime = now.toTimeString().split(" ")[0]; // "HH:MM:SS"
const displayStart = formatToHHMM(startTime);
const dbDate = formatDateForDatabase(now); // Use server date
const displayDate = formatDateForDisplay(now);
```

---

### 2. Backend Changes - `backend/insert_task_logs.php`

#### Added Server Time Validation
```php
// 🔐 SECURITY: Validate the received time is close to server time
// Allow 5-second tolerance for network latency
$server_time = time();
$received_time = strtotime("$date $start_time");
$time_diff = abs($server_time - $received_time);

if ($time_diff > 300) { // 300 seconds = 5 minutes
    echo json_encode([
        'status' => 'error', 
        'message' => 'Time validation failed. Your system time appears to be incorrect.',
        'server_time_diff' => $time_diff
    ]);
    exit;
}
```

#### Uses Server Time for Database Insertion
```php
// 🔐 Use current server time for database insertion
// This ensures even if frontend sends manipulated time, we use server-verified time
$server_date = date("Y-m-d");
$server_time_formatted = date("H:i:s");

// Full datetime for start_time using SERVER TIME
$start_datetime = "$server_date $server_time_formatted";
```

#### Date Validation
```php
// Verify client date matches today's server date
if ($date !== $server_date) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Date mismatch. Your system date is incorrect.',
        'expected_date' => $server_date,
        'received_date' => $date
    ]);
    exit;
}
```

---

### 3. Backend Changes - `backend/update_task_log.php`

#### Added Server Time Validation for End Times
```php
// 🔐 SECURITY: Validate the received end time is close to server time
$server_time = time();
$received_time_full = date("Y-m-d") . " " . $end_time;
$received_timestamp = strtotime($received_time_full);
$time_diff = abs($server_time - $received_timestamp);

if ($time_diff > 300) { // 300 seconds = 5 minutes
    echo json_encode([
        'status' => 'error', 
        'message' => 'Time validation failed. Your system time appears to be incorrect.',
        'server_time_diff' => $time_diff
    ]);
    exit;
}
```

#### Uses Server Time for Database Update
```php
// 🔐 Use current server time for end_time
$server_time_formatted = date("H:i:s");

// Update with server time
$stmt = $conn->prepare("UPDATE task_logs SET end_time = ?, total_duration = ? WHERE id = ?");
$stmt->bind_param("ssi", $server_time_formatted, $duration, $id);
```

---

## How It Works

### Task Start Flow (Secure)
1. User clicks "Slide to Tag" button
2. Frontend calls `getServerTime()` to fetch current server time via `get_server_time.php`
3. Frontend sends server time to `insert_task_logs.php`
4. Backend validates:
   - Received time is within ±5 minutes of current server time
   - Date matches today's server date
5. Backend uses current server time (not client-provided time) for final database insertion

### Task End Flow (Secure)
1. User tags next task
2. Frontend calls `getServerTime()` to get server time for previous task's end time
3. Frontend sends to `update_task_log.php`
4. Backend validates:
   - Received end time is within ±5 minutes of current server time
5. Backend uses current server time for database update

---

## Security Benefits

| Aspect | Before | After |
|--------|--------|-------|
| **Time Source** | Client PC | Server |
| **Tampering Risk** | High (change PC time) | Low (server time cannot be changed from client) |
| **Validation** | None | Server validates time ±5 min tolerance |
| **Date Validation** | None | Server verifies date matches today |
| **Fallback** | N/A | Fallback to local time only if server unreachable |

---

## Error Messages Users May See

### Time Validation Failed
```
"Time validation failed. Your system time appears to be incorrect. Please sync your PC time with server."
```
**Cause**: User's system time is >5 minutes off from server
**Solution**: Sync PC time with server

### Date Mismatch
```
"Date mismatch. Your system date is incorrect."
```
**Cause**: User's system date doesn't match server date
**Solution**: Update PC date to match server

---

## Technical Specifications

### Server Time Endpoint: `backend/get_server_time.php`
- **Timezone**: Asia/Manila (set in PHP)
- **Format**: ISO 8601 (RFC 3339) via `date("c")`
- **Response**: JSON with `server_time` key

### Time Tolerance
- **Network Latency**: 5 minutes (300 seconds)
- **Rationale**: Accounts for slow networks while preventing obvious tampering

### Timezone
- **System Timezone**: Asia/Manila (hardcoded in PHP)
- **All times stored**: In server timezone

---

## Testing Checklist

- [ ] Test normal task tagging flow
- [ ] Verify start time is captured from server
- [ ] Verify end time is captured from server
- [ ] Test with PC time 1 minute ahead - should work
- [ ] Test with PC time 10 minutes ahead - should fail
- [ ] Test with PC date mismatch - should fail
- [ ] Verify server time endpoint returns correct time
- [ ] Verify network latency doesn't cause false rejections
- [ ] Test fallback to local time if server unreachable

---

## Files Modified

1. **Frontend**:
   - `js/start-tag-task.js` - Added `getServerTime()`, updated `startTask()`

2. **Backend**:
   - `backend/insert_task_logs.php` - Added server time validation and usage
   - `backend/update_task_log.php` - Added server time validation and usage

3. **Already Existed**:
   - `backend/get_server_time.php` - Serves current server time

---

## Backward Compatibility

✅ **Fully Backward Compatible**
- No database schema changes
- No API contract changes
- Amendment request flow unaffected (goes through approval process)
- Export functions unaffected

---

## Future Improvements

1. **Stricter Time Validation**: Consider reducing tolerance to 2-3 minutes
2. **Audit Logging**: Log all time validation failures for admin review
3. **Rate Limiting**: Prevent rapid repeated time validation failures
4. **Admin Dashboard**: Show alerts when users have system time issues
5. **Biometric Verification**: Integrate with fingerprint/face recognition for additional security

---

## Notes for Administrators

- Monitor for "time validation failed" errors in logs
- Educate users to keep their PC time synchronized
- Consider automated time sync via domain policies if possible
- Review audit logs periodically for patterns of tampering attempts

---

**Version**: 1.0  
**Date Implemented**: January 2026  
**Security Impact**: HIGH  
**Backwards Compatible**: YES
