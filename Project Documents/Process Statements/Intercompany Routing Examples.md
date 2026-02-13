# Inter-company Routing Examples — Process Bank Transactions

## Purpose
Capture practical routing examples that used to appear as inline TODO reminders in controller code, and keep them in a maintainable requirements/UAT location.

## Notes
- These are operational examples for classification support.
- They do not replace accounting policy; final posting rules remain subject to bookkeeper review.
- Partner-type choices refer to: SP/CU/QE/BT/MA/ZZ.

## Canonical Examples (20)

1. **Square settlement to CIBC (likely FHS)**  
   Likely route: `CU` or `QE` depending on whether customer allocation is required.

2. **FHS expense via Quick Entry**  
   Likely route: `QE` to FHS books with the configured expense template.

3. **Creative Memories payment to CM books**  
   Likely route: `BT` for inter-book transfer, or `MA` if already posted in destination books.

4. **Inter-company reimbursement from FHS to main ops account**  
   Likely route: `BT` (paired transfer if both sides imported).

5. **Card processor fee netted out of deposit**  
   Likely route: `QE` with charge handling.

6. **Vendor refund appears as credit**  
   Likely route: `SP` with credit handling.

7. **Customer overpayment deposit**  
   Likely route: `CU` with optional invoice allocation.

8. **Known existing FA entry found manually**  
   Likely route: `MA` using Existing Type/Entry.

9. **Auto-match candidate confirmed by user**  
   Likely route: `ZZ`.

10. **Internal transfer between CAD and USD bank accounts**  
    Likely route: `BT` with calculated target amount.

11. **Payroll clearing transfer between operating and payroll accounts**  
    Likely route: `BT`.

12. **Expense reimbursement to owner (already posted in GL)**  
    Likely route: `MA`.

13. **Cheque deposit from customer with invoice reference**  
    Likely route: `CU` with allocation.

14. **Subscription software charge recurring monthly**  
    Likely route: `QE` (template-based recurring classification).

15. **Merchant payout split across principal + fee**  
    Likely route: `QE` with charges.

16. **Supplier payment correction after wrong DC indicator**  
    Likely route: toggle DC, then `SP`.

17. **Two-sided transfer imported separately (same amount/date window)**  
    Likely route: paired processing (`Process both sides`) and `BT`.

18. **Deposit tied to prior unmatched GL transaction**  
    Likely route: `ZZ` if matched engine identified it; otherwise `MA`.

19. **E-transfer incoming from known customer**  
    Likely route: `CU`.

20. **Inter-entity funding transfer to secondary books**  
    Likely route: `BT` into destination account mapping; document destination book policy in runbook.

## Maintenance
- Keep this list current when new recurring routing patterns appear.
- Any removed inline code examples should be copied here with a dated note in PR description.
