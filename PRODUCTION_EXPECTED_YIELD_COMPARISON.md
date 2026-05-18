# Production Expected Yield Comparison Feature

## Overview
Added the ability for users to encode expected yield when production reaches the "harvested" stage, enabling comparison between expected and actual yields with variance calculation.

## Features Implemented

### 1. Expected Yield Input at Harvest
- When marking production as "harvested", users can now enter:
  - **Expected Yield** (kg) - What was anticipated
  - **Actual Yield** (kg) - What was actually harvested
- Real-time variance calculation shows difference
- Both values saved to database for comparison

### 2. Update Expected Yield for Harvested Records
- For records already in "harvested" status
- New "Update Expected Yield" button available
- Allows encoding/updating expected yield after harvest
- Useful when expected yield wasn't known at harvest time

### 3. Yield Variance Display
- Shows difference between actual and expected yield
- Displays both absolute difference (kg) and percentage
- Color-coded:
  - **Green** (+) - Actual yield exceeded expectations
  - **Red** (-) - Actual yield below expectations
- Formula: `Variance = Actual Yield - Expected Yield`
- Percentage: `(Variance / Expected Yield) × 100`

### 4. Real-time Calculation
- JavaScript calculates variance as user types
- Updates immediately without page reload
- Shows formatted result: `+50.00 kg (+10.5%)` or `-30.00 kg (-6.2%)`

## Files Modified

### Controllers
- `app/controllers/ProductionController.php`
  - Updated `updateStatus()` to accept and save `expected_yield` when harvesting
  - Added `updateExpectedYield()` method for updating expected yield on harvested records
  - Validates that record is in "harvested" status before allowing update

### Views
- `app/views/production/detail.php`
  - Added "Expected Yield" input field to harvest form
  - Added "Yield Variance" display showing comparison
  - Added "Update Expected Yield" section for harvested records
  - Added JavaScript for real-time variance calculation
  - Enhanced yield display with color-coded variance

## User Flow

### Scenario 1: Enter Expected Yield at Harvest Time
1. Production record in "growing" status
2. User clicks "Mark Harvested"
3. Form expands showing:
   - Expected Yield (kg) - Pre-filled with initial estimate
   - Actual Yield (kg) - Enter harvested amount
   - Harvest Date
   - Warehouse selection
   - **Variance** - Auto-calculates as user types
4. User enters both expected and actual yields
5. System calculates variance in real-time
6. User clicks "Confirm Harvest"
7. Both yields saved, variance displayed in detail view

### Scenario 2: Update Expected Yield After Harvest
1. Production record already in "harvested" status
2. Expected yield was not entered or needs correction
3. User clicks "Update Expected Yield"
4. Form shows:
   - Expected Yield input (current value)
   - Current Actual Yield (read-only)
5. User enters/updates expected yield
6. Clicks "Update Expected Yield"
7. System recalculates variance
8. Page reloads showing updated comparison

## Variance Calculation

### Formula
```
Variance (kg) = Actual Yield - Expected Yield
Variance (%) = (Variance / Expected Yield) × 100
```

### Examples

**Example 1: Exceeded Expectations**
- Expected Yield: 500 kg
- Actual Yield: 550 kg
- Variance: **+50.00 kg (+10.0%)**
- Display: Green text, positive indicator

**Example 2: Below Expectations**
- Expected Yield: 800 kg
- Actual Yield: 720 kg
- Variance: **-80.00 kg (-10.0%)**
- Display: Red text, negative indicator

**Example 3: Met Expectations**
- Expected Yield: 1000 kg
- Actual Yield: 1000 kg
- Variance: **+0.00 kg (0.0%)**
- Display: Green text (technically positive)

## Display Format

### In Detail View
```
Expected Yield: 500.00 kg
Actual Yield: 550.00 kg (green, bold)
Yield Variance: +50.00 kg (+10.0%) (green, bold)
```

### In Harvest Form (Real-time)
```
Expected Yield: [500.00]
Actual Yield: [550.00]
Variance: +50.00 kg (+10.0%) (green, bold, auto-updates)
```

## Benefits

1. **Performance Tracking** - Compare planned vs actual yields
2. **Accuracy Improvement** - Learn from variance patterns
3. **Decision Making** - Data-driven planning for future crops
4. **Accountability** - Track estimation accuracy over time
5. **Reporting** - Generate variance reports for analysis
6. **Flexibility** - Update expected yield even after harvest
7. **Real-time Feedback** - See variance immediately while entering data

## Use Cases

### Use Case 1: Farmer Estimation Accuracy
- Farmer estimates 500 kg yield
- Actually harvests 550 kg
- System shows +10% variance
- **Insight**: Farmer tends to underestimate, adjust future estimates

### Use Case 2: Weather Impact Analysis
- Expected 800 kg based on normal conditions
- Drought reduces yield to 600 kg
- System shows -25% variance
- **Insight**: Document weather impact for insurance/planning

### Use Case 3: New Variety Testing
- Testing new seed variety
- Expected 1000 kg based on supplier claims
- Actually harvests 1200 kg
- System shows +20% variance
- **Insight**: Variety performs better than expected, consider expanding

### Use Case 4: Post-Harvest Analysis
- Harvest completed without expected yield data
- Later, agronomist reviews and enters expected yield
- System calculates variance retroactively
- **Insight**: Complete historical data for reporting

## Database Schema

### production_records table
- `expected_yield` DECIMAL(10,2) - Expected harvest amount (kg)
- `actual_yield` DECIMAL(10,2) - Actual harvested amount (kg)
- Both fields used to calculate variance

## Validation Rules

1. Expected yield must be greater than zero
2. Actual yield must be greater than zero for harvest
3. Can only update expected yield for records in "harvested" status
4. Variance calculated only when both values present

## Testing Checklist

- [x] Mark production as harvested with expected yield
- [x] Verify variance calculates correctly (positive)
- [x] Verify variance calculates correctly (negative)
- [x] Verify variance calculates correctly (zero)
- [x] Test real-time calculation in harvest form
- [x] Update expected yield on harvested record
- [x] Verify color coding (green for positive, red for negative)
- [x] Verify percentage calculation accuracy
- [x] Test with decimal values
- [x] Verify validation (expected yield > 0)

## Future Enhancements

- Variance reports by farmer
- Variance reports by product/variety
- Variance trends over time
- Average variance by season
- Alerts for significant variances
- Variance-based recommendations

## Notes

- Expected yield can be entered at harvest time or updated later
- Variance only displays when both expected and actual yields are present
- Real-time calculation provides immediate feedback
- Color coding helps quickly identify over/under performance
- Percentage variance helps compare across different scale productions
- Historical data preserved for analysis and reporting

## Status
✅ **COMPLETE** - Expected yield encoding and variance comparison fully implemented
