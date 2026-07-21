# Timezone Fix - PH Timezone (Asia/Manila) Implementation

## Problem Statement
Employees in different timezones (e.g., Australia, US, etc.) were seeing task times converted to their local device timezone instead of Philippines timezone (PH), causing confusion and potential time tracking issues.

**Example Issue**: An employee with AU timezone would see a 14:00 PH task time as 16:00 AU time on their device.

## Solution Overview
Implemented a **system-wide timezone lock to Asia/Manila** that:
- ✅ Always uses PH timezone regardless of device settings
- ✅ Prevents timezone conversion at display level
- ✅ Shows visual indicator of PH timezone to users
- ✅ Validates times against PH server time

---

## How It Works

### Frontend Flow (JavaScript)

#### 1. Server Time Fetching
```javascript
async function getServerTime() {
  // Fetches time from get_server_time.php
  // Returns: timestamp, ISO format, timezone info, PH formatted time
  const data = await fetch("../backend/get_server_time.php").then(r => r.json());
  
  // Converts server timestamp to PH timezone using Intl API
  // Intl.DateTimeFormat with timeZone: 'Asia/Manila'
}
```

#### 2. PH Timezone Formatting
```javascript
function formatTimeInPHTimezone(date) {
  // Uses Intl.DateTimeFormat with timeZone: 'Asia/Manila'
  // Ensures time is displayed in PH timezone regardless of browser timezone
}

function formatDateInPHTimezone(date) {
  // Returns date in YYYY-MM-DD format in PH timezone
}
```

#### 3. Local Date String in PH Timezone
```javascript
function getLocalDateString() {
  // Previously: Used device local timezone
  // Now: Uses Asia/Manila timezone via Intl API
  // Returns: YYYY-MM-DD in PH timezone
}
```

### Backend Flow (PHP)

#### 1. Enhanced Server Time Endpoint
```php
// File: backend/get_server_time.php
date_default_timezone_set("Asia/Manila");

// Returns:
{
    "server_time": "2026-01-19T14:30:45+08:00",     // ISO 8601 with TZ
    "server_timestamp": 1737270645,                  // Unix timestamp (TZ-independent)
    "timezone": "Asia/Manila",                       // Explicit timezone
    "timezone_offset": "+08:00",                     // UTC offset
    "formatted_time": "2026-01-19 14:30:45"         // PH formatted
}
```

#### 2. Backend Time Validation
- Uses `date_default_timezone_set("Asia/Manila")` in all PHP files
- Validates times against PH server time (not browser time)
- Stores all times in PH timezone

---

## Technical Implementation Details

### JavaScript Timezone Handling

**Key Technology**: `Intl.DateTimeFormat` with `timeZone` option

```javascript
const formatter = new Intl.DateTimeFormat('en-US', {
  timeZone: 'Asia/Manila',
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
  hour12: false
});

// This returns time in PH timezone regardless of browser timezone
const phTime = formatter.format(new Date());
```

**Why This Approach**:
- ✅ Works across all browsers and devices
- ✅ Handles daylight saving time automatically
- ✅ No external libraries needed
- ✅ No timezone conversion errors

### Files Modified

**Backend**:
1. `backend/get_server_time.php` - Enhanced to return timezone info
2. `backend/insert_task_logs.php` - Already uses Asia/Manila timezone
3. `backend/update_task_log.php` - Already uses Asia/Manila timezone

**Frontend**:
1. `js/start-tag-task.js`:
   - Added `formatTimeInPHTimezone()`
   - Added `formatDateInPHTimezone()`
   - Updated `getServerTime()` to use PH timezone
   - Updated `formatTime()` to use PH timezone
   - Updated `formatDateForDatabase()` to use PH timezone
   - Updated `getLocalDateString()` to use PH timezone
   - Added `displayPHServerTime()` for UI display

2. `dashboards/admin-dashboard.php`:
   - Added `phServerTimeDisplay` element to show current PH time

---

## User Experience

### What Users See

**Before**:
```
Live Time: 14:30 (in their device's local timezone)
Task Time: 14:30 (may be converted to their device timezone)
```

**After**:
```
Live Time: 14:30 (PH Time)
🕐 PH Time: 14:30:45 (Asia/Manila)  ← Shows explicit timezone
Task Time: 14:30 (always PH timezone)
```

### Benefits for Employees in Different Timezones

**Example: Employee with AU Timezone (+10:00)**

| Scenario | Before | After |
|----------|--------|-------|
| Server logs task at 14:00 PH | Shows 16:00 (AU time - WRONG) | Shows 14:00 (PH time - CORRECT) |
| User checks their time | 16:00 on AU device | Still 16:00 on AU device, but sees 14:00 PH in app |
| Team coordination | Confusion due to timezone mismatch | Clear that everyone uses PH time |

---

## Timezone Indicator on Dashboard

The system now displays:
```
🕐 PH Time: 14:30:45 (Asia/Manila)
```

**Benefits**:
- ✅ Reminds users they're on PH timezone
- ✅ Helps identify if server/PH time is different from device time
- ✅ Updates every second so users can verify sync
- ✅ Color-coded: Green = synced, Red/Gray = error/loading

---

## Testing Checklist

### Test 1: User with AU Timezone
- [ ] Change device timezone to Australia/Brisbane
- [ ] Log times in app
- [ ] Verify times show in PH timezone (not AU timezone)
- [ ] Verify `phServerTimeDisplay` shows PH time

### Test 2: User with US Timezone
- [ ] Change device timezone to US/Eastern
- [ ] Repeat Test 1
- [ ] Verify consistency

### Test 3: Timezone Display
- [ ] Verify `🕐 PH Time: HH:MM:SS (Asia/Manila)` appears on dashboard
- [ ] Verify it updates every second
- [ ] Verify it matches server time

### Test 4: Database Storage
- [ ] Log a task
- [ ] Check database directly
- [ ] Verify stored time is in PH timezone

### Test 5: Different Device Timezones
- [ ] Multiple users log in from different timezones
- [ ] All should see same PH times
- [ ] No timezone conversion differences

---

## API Response Structure

### `get_server_time.php` Response
```json
{
  "server_time": "2026-01-19T14:30:45+08:00",
  "server_timestamp": 1737270645,
  "timezone": "Asia/Manila",
  "timezone_offset": "+08:00",
  "formatted_time": "2026-01-19 14:30:45"
}
```

**Usage**:
- **JavaScript**: Use `server_timestamp` + `Intl.DateTimeFormat` with `timeZone: 'Asia/Manila'`
- **Backend**: Use `formatted_time` or `timezone_offset` for validation
- **Display**: Use `formatted_time` or construct from `server_timestamp`

---

## Security & Validation

### Time Validation Still Works
Even with PH timezone fix, the existing security validations remain:

```php
// backend/insert_task_logs.php
$time_diff = abs($server_time - $received_time);
if ($time_diff > 300) { // 5-minute tolerance
    // Reject if device time is >5 min off
}
```

### What This Means
- ✅ Even if user changes device timezone AND device time, system validates
- ✅ If user is in PH timezone, no issue
- ✅ If user is in different timezone, server validates based on server time
- ✅ User can't log times from the future or far past

---

## Backward Compatibility

✅ **Fully Backward Compatible**
- No database schema changes
- No API contract breaking changes
- Existing time entries unaffected
- Amendment request flow works same as before
- Export functions work same as before

---

## Browser Compatibility

| Browser | Intl.DateTimeFormat | Timezone Support |
|---------|-------------------|------------------|
| Chrome | ✅ Yes | ✅ Full |
| Firefox | ✅ Yes | ✅ Full |
| Safari | ✅ Yes | ✅ Full |
| Edge | ✅ Yes | ✅ Full |
| IE 11 | ⚠️ Partial | ⚠️ Limited |

**For IE 11**: Falls back to server-formatted time

---

## Troubleshooting

### Issue: PH Time Display Shows Wrong Time
**Solution**: 
- Verify `get_server_time.php` is accessible
- Check server timezone is set to Asia/Manila
- Clear browser cache and reload

### Issue: Times Stored as Local Timezone
**Solution**:
- Ensure PHP timezone is set: `date_default_timezone_set("Asia/Manila")`
- Restart PHP/Web server
- Check database times using: `SELECT DATE_FORMAT(start_time, '%Y-%m-%d %H:%i:%s') FROM task_logs`

### Issue: Amendment Times Show Wrong Timezone
**Solution**:
- Amendment backend also uses `date_default_timezone_set("Asia/Manila")`
- Verify backend files have timezone setting
- Check submitted times are validated against PH time

---

## Future Enhancements

1. **Admin Timezone Dashboard**: Show timezone status for all logged-in users
2. **Timezone Conflict Detection**: Alert if user's device timezone differs significantly from PH
3. **Automated Time Sync**: Suggest automatic device time sync if detected off
4. **Timezone Audit Log**: Log all timezone-related actions for compliance
5. **Multi-Timezone Support**: If company expands to other regions

---

## Notes for Administrators

### Monitoring
- Check `phServerTimeDisplay` appears correctly on all user dashboards
- Monitor for time validation failures in users' different timezones
- Verify database times are consistent (all in PH timezone)

### User Education
- Inform employees that system always uses PH timezone
- Explain they'll see PH time in app even if device timezone differs
- Provide instructions for device timezone settings if needed

### Deployment Checklist
- [ ] Verify all PHP files have `date_default_timezone_set("Asia/Manila")`
- [ ] Test with users in different timezones
- [ ] Verify `phServerTimeDisplay` shows on dashboard
- [ ] Check database for PH timezone consistency
- [ ] Document timezone behavior in user guide

---

**Version**: 1.0  
**Date Implemented**: January 2026  
**Compatibility**: All modern browsers (IE 11 has limited support)  
**Backwards Compatible**: YES  
**Impact**: ALL USERS - Ensures consistent PH timezone across all devices
