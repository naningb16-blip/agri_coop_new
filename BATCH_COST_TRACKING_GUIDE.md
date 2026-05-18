# Batch Cost Tracking Guide

## Where Batch Costs Appear After Adding

When you add a batch cost in the Monitoring module, it appears in multiple places:

### 1. **Batch Cost Analysis Table** (Main View)
**Location:** Monitoring Module → Main Page

Shows aggregated costs per batch:
- **Batch Number** - Links to processing batch details
- **Product** - What's being processed
- **Status** - Current batch status
- **Input/Output Quantities** - Raw materials vs finished goods
- **Total Cost** - Sum of ALL cost entries for this batch
- **Cost per Unit** - Total cost divided by output quantity
- **Details Button** - Click to see individual cost entries

### 2. **Batch Cost Details Modal** (New Feature)
**How to Access:** Click the "Details" button next to any batch with costs

Shows individual cost entries:
- **Date** - When the cost was recorded
- **Cost Type** - Labor, Material, Overhead, Utility, or Other
- **Description** - Details about the cost
- **Amount** - Cost amount in pesos
- **Recorded By** - Who added the cost entry
- **Total** - Sum of all entries (shown in footer)

### 3. **Batch Cost Breakdown** (Summary Card)
**Location:** Monitoring Module → Top Section (Left Card)

Shows costs grouped by type:
- Labor costs
- Material costs
- Overhead costs
- Utility costs
- Other costs

Each type shows:
- Total amount
- Percentage of total costs (progress bar)

### 4. **Summary Statistics** (Top Cards)
**Location:** Monitoring Module → Top Row

Three summary cards:
- **Batch Processing Costs** - Total of all batch costs
- **Production Input Costs** - Costs from production inputs
- **Total Costs** - Combined total

## How Batch Costs Are Calculated

### Total Cost per Batch
```
Total Cost = SUM(all cost entries for that batch)
```

### Cost per Unit
```
Cost per Unit = Total Cost / Output Quantity
```

If output quantity is 0 or null, cost per unit shows "—"

## Workflow Example

1. **Add a Batch Cost:**
   - Click "Add Batch Cost" button
   - Select a processing batch from dropdown
   - Choose cost type (e.g., Labor)
   - Enter amount (e.g., 5000.00)
   - Add description (e.g., "Worker wages for sorting")
   - Submit

2. **View in Main Table:**
   - The batch's "Total Cost" increases by 5000.00
   - "Cost per Unit" recalculates automatically
   - Page refreshes to show updated data

3. **View Details:**
   - Click "Details" button for that batch
   - Modal opens showing all cost entries
   - See the new entry with date, type, description, amount
   - See who recorded it and when

4. **View in Breakdown:**
   - "Labor" section in "Batch Cost Breakdown" increases
   - Progress bar adjusts to show new percentage
   - "Batch Processing Costs" summary card updates

## Cost Types Explained

| Type | Description | Examples |
|------|-------------|----------|
| **Labor** | Human resources costs | Worker wages, overtime, contractor fees |
| **Material** | Raw materials and supplies | Packaging materials, cleaning supplies, consumables |
| **Overhead** | Indirect costs | Rent, insurance, administrative costs |
| **Utility** | Energy and services | Electricity, water, gas, internet |
| **Other** | Miscellaneous costs | Repairs, maintenance, unexpected expenses |

## Date Filtering

Use the date filter at the top to view costs for specific periods:
- **From Date** - Start of period
- **To Date** - End of period
- Click "Apply" to filter

This affects:
- Which batches appear in the table
- Cost breakdown calculations
- Summary statistics

## Permissions

- **Regular Users:** Can add and view batch costs
- **GM (General Manager):** Can view all costs but cannot add (read-only)

## Tips

1. **Be Specific in Descriptions** - Helps with auditing and analysis
2. **Record Costs Promptly** - Don't wait until batch completion
3. **Use Correct Cost Types** - Makes breakdown analysis more useful
4. **Check Details Regularly** - Verify all costs are recorded
5. **Use Date Filters** - Analyze costs by period for trends

## Integration with Other Modules

### Processing Module
- Batch costs link to processing batches
- Batch number in cost table links to processing detail page
- Costs help calculate profitability per batch

### Finance Module
- Batch costs can be used for expense tracking
- Cost data helps with budgeting and forecasting
- Supports cost accounting and analysis

### Reports Module
- Cost data feeds into various reports
- Helps with cost analysis and optimization
- Supports decision-making on pricing

## Troubleshooting

### "No processing batches available"
**Solution:** Create a processing batch first in the Processing module

### "Invalid batch ID"
**Solution:** The batch may have been deleted. Select a different batch.

### Costs not showing
**Solution:** Check your date filter - costs may be outside the selected period

### Details button shows "No costs"
**Solution:** No cost entries have been added for that batch yet

---
**Last Updated:** May 6, 2026  
**Module:** Monitoring / Cost Tracking
