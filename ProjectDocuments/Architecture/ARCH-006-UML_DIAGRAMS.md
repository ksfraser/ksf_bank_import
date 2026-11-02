# UML Diagrams - Paired Transfer Processing Architecture

## Class Diagram

### Complete Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                        PAIRED TRANSFER ARCHITECTURE                               │
└──────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              PRESENTATION LAYER                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌────────────────────────────────────────────────────────────────┐             │
│  │              process_statements.php                             │             │
│  ├────────────────────────────────────────────────────────────────┤             │
│  │ + ProcessBothSides handler                                     │             │
│  │ + Display transactions table                                   │             │
│  └───────────────┬────────────────────────────────────────────────┘             │
│                  │ uses                                                          │
│                  ▼                                                               │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              SERVICE LAYER                                       │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌──────────────────────────────────────────────────────────────┐               │
│  │      «orchestrator»                                           │               │
│  │      PairedTransferProcessor                                  │               │
│  ├──────────────────────────────────────────────────────────────┤               │
│  │ - biTransactions: bi_transactions_model                       │               │
│  │ - vendorList: array                                           │               │
│  │ - optypes: array                                              │               │
│  │ - bankTransferFactory: BankTransferFactoryInterface          │               │
│  │ - transactionUpdater: TransactionUpdater                     │               │
│  │ - directionAnalyzer: TransferDirectionAnalyzer               │               │
│  ├──────────────────────────────────────────────────────────────┤               │
│  │ + __construct(...)                                            │               │
│  │ + processPairedTransfer(transactionId: int): array           │               │
│  └───────┬──────────────┬────────────────┬───────────────────────┘               │
│          │              │                │                                       │
│          │ delegates    │ delegates      │ delegates                             │
│          ▼              ▼                ▼                                       │
│  ┌──────────────┐  ┌─────────────────┐  ┌──────────────────┐                   │
│  │«business»    │  │«integration»    │  │«persistence»     │                   │
│  │Transfer      │  │BankTransfer     │  │Transaction       │                   │
│  │Direction     │  │Factory          │  │Updater           │                   │
│  │Analyzer      │  │                 │  │                  │                   │
│  ├──────────────┤  ├─────────────────┤  ├──────────────────┤                   │
│  │+ analyze()   │  │+ createTransfer()│  │+ update          │                   │
│  │              │  │  : array        │  │  Paired          │                   │
│  │              │  │+ validate       │  │  Transactions()  │                   │
│  │              │  │  TransferData() │  │                  │                   │
│  └──────────────┘  └─────────────────┘  └──────────────────┘                   │
│         │                   │                     │                              │
│         │                   │                     │                              │
└─────────────────────────────────────────────────────────────────────────────────┘
          │                   │                     │
          │                   │                     ▼
          │                   │            ┌──────────────────────────┐
          │                   │            │ Global Functions          │
          │                   │            ├──────────────────────────┤
          │                   │            │ update_transactions()    │
          │                   │            │ set_bank_partner_data()  │
          │                   │            └──────────────────────────┘
          │                   │
          │                   ▼
          │          ┌─────────────────────────┐
          │          │ FrontAccounting API     │
          │          ├─────────────────────────┤
          │          │ fa_bank_transfer class  │
          │          │ begin_transaction()     │
          │          │ commit_transaction()    │
          │          └─────────────────────────┘
          │
          ▼ returns direction

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              MANAGER LAYER                                       │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌────────────────────────────┐      ┌──────────────────────────────┐           │
│  │ «singleton»                │      │ «singleton»                   │           │
│  │ VendorListManager          │      │ OperationTypesRegistry        │           │
│  ├────────────────────────────┤      ├──────────────────────────────┤           │
│  │ - instance: static         │      │ - instance: static            │           │
│  │ - vendorList: array        │      │ - types: array                │           │
│  │ - lastLoaded: int          │      ├──────────────────────────────┤           │
│  │ - cacheDuration: int       │      │ + getInstance(): self         │           │
│  ├────────────────────────────┤      │ + getTypes(): array           │           │
│  │ - __construct()            │      │ + getType(code): string|null │           │
│  │ + getInstance(): self      │      │ + hasType(code): bool         │           │
│  │ + getVendorList(): array   │      │ + reload(): void              │           │
│  │ + clearCache(): void       │      └──────────────────────────────┘           │
│  │ + setCacheDuration(int)    │                    │                             │
│  └────────────────────────────┘                    │ loads                       │
│             │                                       ▼                             │
│             │ loads                    ┌──────────────────────────────┐           │
│             ▼                          │ OperationTypes/*             │           │
│  ┌────────────────────────────┐       │ Plugin Directory             │           │
│  │ Database: Vendor List      │       ├──────────────────────────────┤           │
│  │ (Session Cached)           │       │ Default: SP, CU, QE, BT,    │           │
│  └────────────────────────────┘       │          MA, ZZ              │           │
│                                        │ Custom: (plugin classes)     │           │
│                                        └──────────────────────────────┘           │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Sequence Diagram - Paired Transfer Processing

```
User               process_      Paired          Transfer        Bank           Transaction
                  statements.   Transfer        Direction       Transfer       Updater
                     php        Processor       Analyzer        Factory
 │                    │              │               │              │               │
 │ Click "Process     │              │               │              │               │
 │ Both Sides"        │              │               │              │               │
 │────────────────────>              │               │              │               │
 │                    │              │               │              │               │
 │                    │ processPairedTransfer(id)    │              │               │
 │                    │─────────────>│               │              │               │
 │                    │              │               │              │               │
 │                    │              │ Load Transaction 1          │               │
 │                    │              │───┐           │              │               │
 │                    │              │   │           │              │               │
 │                    │              │<──┘           │              │               │
 │                    │              │               │              │               │
 │                    │              │ Load Transaction 2          │               │
 │                    │              │───┐           │              │               │
 │                    │              │   │           │              │               │
 │                    │              │<──┘           │              │               │
 │                    │              │               │              │               │
 │                    │              │ Load Accounts │              │               │
 │                    │              │───┐           │              │               │
 │                    │              │   │           │              │               │
 │                    │              │<──┘           │              │               │
 │                    │              │               │              │               │
 │                    │              │ analyze(trz1, trz2, acc1, acc2)            │
 │                    │              │──────────────>│              │               │
 │                    │              │               │              │               │
 │                    │              │               │ Validate Inputs             │
 │                    │              │               │───┐          │               │
 │                    │              │               │   │          │               │
 │                    │              │               │<──┘          │               │
 │                    │              │               │              │               │
 │                    │              │               │ Determine Direction         │
 │                    │              │               │ (DC = 'D' or 'C')           │
 │                    │              │               │───┐          │               │
 │                    │              │               │   │          │               │
 │                    │              │               │<──┘          │               │
 │                    │              │               │              │               │
 │                    │              │               │ Build Transfer Data         │
 │                    │              │               │───┐          │               │
 │                    │              │               │   │          │               │
 │                    │              │               │<──┘          │               │
 │                    │              │               │              │               │
 │                    │              │<──────────────│              │               │
 │                    │              │  {from, to, amount, date}    │               │
 │                    │              │               │              │               │
 │                    │              │ createTransfer(transferData) │               │
 │                    │              │──────────────────────────────>               │
 │                    │              │               │              │               │
 │                    │              │               │              │ Validate Data │
 │                    │              │               │              │───┐           │
 │                    │              │               │              │   │           │
 │                    │              │               │              │<──┘           │
 │                    │              │               │              │               │
 │                    │              │               │              │ Call FA API   │
 │                    │              │               │              │ (fa_bank_     │
 │                    │              │               │              │  transfer)    │
 │                    │              │               │              │───┐           │
 │                    │              │               │              │   │           │
 │                    │              │               │              │<──┘           │
 │                    │              │               │              │               │
 │                    │              │<──────────────────────────────               │
 │                    │              │  {trans_no, trans_type}      │               │
 │                    │              │               │              │               │
 │                    │              │ updatePairedTransactions(result, transferData)
 │                    │              │──────────────────────────────────────────────>
 │                    │              │               │              │               │
 │                    │              │               │              │               │ Update
 │                    │              │               │              │               │ Transaction 1
 │                    │              │               │              │               │───┐
 │                    │              │               │              │               │   │
 │                    │              │               │              │               │<──┘
 │                    │              │               │              │               │
 │                    │              │               │              │               │ Update
 │                    │              │               │              │               │ Transaction 2
 │                    │              │               │              │               │───┐
 │                    │              │               │              │               │   │
 │                    │              │               │              │               │<──┘
 │                    │              │               │              │               │
 │                    │              │<──────────────────────────────────────────────
 │                    │              │               │              │               │
 │                    │<─────────────│               │              │               │
 │                    │  {trans_no, trans_type}      │              │               │
 │                    │              │               │              │               │
 │<────────────────────              │               │              │               │
 │ Display Success    │              │               │              │               │
 │ Notification       │              │               │              │               │
 │                    │              │               │              │               │
```

---

## Component Diagram

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         SYSTEM COMPONENTS                                   │
└────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  USER INTERFACE COMPONENT                                            │
│  ┌────────────────────────────────────────────────────────┐         │
│  │  process_statements.php                                 │         │
│  │  - Transaction list display                             │         │
│  │  - "Process Both Sides" button                          │         │
│  │  - Visual indicators (✓ checkmarks, links)             │         │
│  └────────────────────────────────────────────────────────┘         │
└────────┬───────────────────────────────────────────────────────────┘
         │
         │ HTTP POST
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  CORE SERVICE COMPONENT                                              │
│  ┌────────────────────────────────────────────────────────┐         │
│  │  Services/                                              │         │
│  │  ├── PairedTransferProcessor                            │         │
│  │  ├── TransferDirectionAnalyzer                          │         │
│  │  ├── BankTransferFactory                                │         │
│  │  └── TransactionUpdater                                 │         │
│  └────────────────────────────────────────────────────────┘         │
└───┬────────────┬──────────────────────────────────┬─────────────────┘
    │            │                                  │
    │ uses       │ uses                             │ uses
    ▼            ▼                                  ▼
┌────────────┐ ┌──────────────────┐  ┌──────────────────────────────┐
│  MANAGER   │ │  FA INTEGRATION  │  │  DATA ACCESS COMPONENT        │
│  COMPONENT │ │  COMPONENT       │  │  ┌────────────────────────┐  │
│  ┌────────┐│ │  ┌──────────────┐│  │  │ bi_transactions_model  │  │
│  │Vendor  ││ │  │fa_bank_      ││  │  │ - get_transaction()    │  │
│  │List    ││ │  │transfer      ││  │  │ - get_account()        │  │
│  │Manager ││ │  │              ││  │  └────────────────────────┘  │
│  │        ││ │  │- begin_      ││  │  ┌────────────────────────┐  │
│  │Session ││ │  │  transaction ││  │  │ Global Functions       │  │
│  │Cached  ││ │  │- commit_     ││  │  │ - update_transactions()│  │
│  └────────┘│ │  │  transaction ││  │  │ - set_bank_partner_   │  │
│  ┌────────┐│ │  │- add_bank_   ││  │  │   data()               │  │
│  │Operation││ │  │  transfer    ││  │  └────────────────────────┘  │
│  │Types   ││ │  └──────────────┘│  └──────────────────────────────┘
│  │Registry││ └──────────────────┘
│  │        ││
│  │Plugin  ││
│  │Support ││
│  └────────┘│
└────────────┘
     │
     │ loads
     ▼
┌──────────────────────────────┐
│  CONFIGURATION COMPONENT      │
│  ┌────────────────────────┐  │
│  │ Session Cache          │  │
│  │ - Vendor List          │  │
│  │ - Operation Types      │  │
│  └────────────────────────┘  │
│  ┌────────────────────────┐  │
│  │ OperationTypes/        │  │
│  │ - Plugin Directory     │  │
│  │ - Default Types        │  │
│  └────────────────────────┘  │
└──────────────────────────────┘
```

---

## State Diagram - Transaction Processing States

```
┌─────────────────────────────────────────────────────────────────────┐
│              TRANSACTION STATE MACHINE                               │
└─────────────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │   IMPORTED   │
                    │  (status=0)  │
                    └──────┬───────┘
                           │
                           │ User clicks
                           │ "Process Both Sides"
                           ▼
                    ┌──────────────┐
                    │  VALIDATING  │
                    │              │
                    └──────┬───────┘
                           │
                ┌──────────┴──────────┐
                │                     │
         [Valid Pair]          [Invalid/Error]
                │                     │
                ▼                     ▼
         ┌──────────────┐      ┌──────────────┐
         │  ANALYZING   │      │    ERROR     │
         │  DIRECTION   │      │              │
         └──────┬───────┘      └──────────────┘
                │
                │ Determine FROM/TO
                ▼
         ┌──────────────┐
         │   CREATING   │
         │  FA TRANSFER │
         └──────┬───────┘
                │
                │ begin_transaction()
                ▼
         ┌──────────────┐
         │   UPDATING   │
         │ TRANSACTIONS │
         └──────┬───────┘
                │
                │ commit_transaction()
                ▼
         ┌──────────────┐
         │  PROCESSED   │
         │  (status=1)  │
         │              │
         │ ✓ Checkmark  │
         │ FA Link      │
         └──────────────┘
```

---

## Deployment Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                    DEPLOYMENT ARCHITECTURE                           │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  WEB SERVER (Apache/Nginx + PHP 7.4+)                               │
│  ┌────────────────────────────────────────────────────────┐         │
│  │  FrontAccounting Installation                           │         │
│  │  ┌──────────────────────────────────────────┐          │         │
│  │  │  modules/ksf_bank_import/                │          │         │
│  │  │  ├── process_statements.php              │          │         │
│  │  │  ├── Services/                            │          │         │
│  │  │  │   ├── PairedTransferProcessor.php     │          │         │
│  │  │  │   ├── TransferDirectionAnalyzer.php   │          │         │
│  │  │  │   ├── BankTransferFactory.php          │          │         │
│  │  │  │   └── TransactionUpdater.php           │          │         │
│  │  │  ├── VendorListManager.php                │          │         │
│  │  │  ├── OperationTypes/                      │          │         │
│  │  │  │   └── OperationTypesRegistry.php       │          │         │
│  │  │  └── tests/                                │          │         │
│  │  └──────────────────────────────────────────┘          │         │
│  └────────────────────────────────────────────────────────┘         │
└────────┬──────────────────────────────────────────────────┬─────────┘
         │                                                  │
         │ SQL Queries                                      │ Session
         │                                                  │ Storage
         ▼                                                  ▼
┌──────────────────────────┐                    ┌────────────────────┐
│  DATABASE SERVER         │                    │  SESSION CACHE     │
│  (MySQL/MariaDB)         │                    │  ┌──────────────┐  │
│  ┌────────────────────┐  │                    │  │ vendor_list  │  │
│  │ imported_bank_     │  │                    │  │ operation_   │  │
│  │ transactions       │  │                    │  │ types        │  │
│  │                    │  │                    │  └──────────────┘  │
│  │ bank_trans         │  │                    │                    │
│  │                    │  │                    │  ~95% Performance  │
│  │ vendors            │  │                    │  Improvement       │
│  │                    │  │                    │                    │
│  └────────────────────┘  │                    └────────────────────┘
└──────────────────────────┘
```

---

## Author
**Kevin Fraser**  
**Date:** October 18, 2025  
**Version:** 1.0.0
