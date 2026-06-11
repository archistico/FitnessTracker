# Testing

## Regola operativa

Ogni step deve lasciare il progetto in uno stato verificabile. Prima di consegnare o passare allo step successivo, eseguire almeno:

```powershell
make check-code
make check
```

`make check-code` esegue controlli che non richiedono lo schema database già allineato. `make check` richiede invece database coerente con le Entity.

## SQLite

Non usare:

```powershell
php bin/console doctrine:database:create
```

Con SQLite il database è un file. In questa fase iniziale, mentre il modello dati cambia spesso, il comando più affidabile è:

```powershell
make reset-db
```

`make reset-db` elimina `var/data/app.db`, crea lo schema dalle Entity, carica le fixture, valida lo schema ed esegue PHPUnit. Quando il dominio sarà più stabile, si passerà a migration Doctrine rigenerate automaticamente.

Per aggiornare uno schema esistente senza eliminare i dati, solo in sviluppo:

```powershell
make schema-update
make fixtures
make check
```

## Test attuali

Sono presenti test unitari minimi su:

- `Exercise`;
- `Equipment`;
- `AvailableExerciseFilter`;
- `WorkoutPlan`;
- `WorkoutPlanExercise`.

I test funzionali sulle pagine arriveranno dopo la stabilizzazione delle prime route CRUD.

## Verifiche manuali minime Step 3

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make reset-db
make check
```

Verificare nel browser:

- `/` mostra conteggi reali, inclusa la card schede;
- `/workout-plans` mostra le schede caricate dalle fixture;
- `/workout-plans/new` permette di creare una nuova scheda;
- `/workout-plans/{slug}` mostra gli esercizi pianificati e consente di aggiungerne altri;
- `/exercises?available=1` continua a filtrare in base alle attrezzature disponibili.

## Verifiche manuali minime Step 4

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make reset-db
make check
```

Verificare nel browser:

- `/workout-plans` mostra le schede;
- aprire `/workout-plans/lunedi-gambe` e premere “Avvia allenamento”;
- la pagina `/workout-sessions/{id}` mostra esercizi e serie generate;
- salvare almeno una serie con peso/reps/RIR/percezione;
- `/workout-sessions` mostra la sessione nel diario;
- chiudere la sessione e verificare lo stato completata/parziale.

Nuovi test unitari aggiunti:

- `WorkoutSessionFactoryTest`;
- `SetLogTest`.

## Verifiche manuali minime Step 5

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make reset-db
make check
```

Verificare nel browser:

- avviare una scheda da `/workout-plans/lunedi-gambe`;
- salvare una serie con peso/reps/RIR;
- marcare una serie come saltata con motivazione;
- marcare un esercizio come saltato;
- verificare che il riepilogo sessione aggiorni completate/saltate/rimanenti;
- chiudere la sessione e controllare che una sessione con serie saltate risulti parziale.

Nuovi test unitari aggiunti/aggiornati:

- `SetLogTest` verifica la marcatura serie saltata;
- `WorkoutSessionSummaryTest` verifica riepilogo sessione e chiusura con esercizi saltati.

## Verifiche manuali minime Step 6

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make check
```

Verificare nel browser da finestra stretta o smartphone:

- avviare una scheda da `/workout-plans/lunedi-gambe`;
- aprire la sessione generata;
- verificare che ogni serie sia una card leggibile e non una tabella larga;
- per un esercizio peso/reps, verificare che siano prioritari Kg, Reps, Recupero, ripetizioni in riserva, percezione del carico, percezione dello sforzo e Note;
- per cardio/tempo, verificare che siano prioritari Secondi, distanza/livello-resistenza quando pertinenti e Sforzo;
- verificare che tutti i campi restino visibili senza blocchi espandibili;
- salvare una serie e controllare che il riepilogo si aggiorni.

Nuovo test unitario aggiunto:

- `ExerciseTrackingModeTest`, per fissare quali campi sono primari per i principali tracking mode.

## Verifiche manuali minime Step 7

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make check
```

Non serve `make reset-db`, perché lo schema database non è cambiato.

Verificare nel browser, idealmente anche da smartphone o finestra stretta:

- `/` usa card riepilogative e azioni rapide grandi;
- `/equipment` mostra l'archivio come card e non come elenco denso;
- `/equipment/{slug}` mostra descrizione, istruzioni e dati come sezioni leggibili;
- `/gym/equipment` mostra ogni attrezzatura con stato e pulsante grande di attivazione/disattivazione;
- `/exercises` mostra esercizi come card con disponibilità, tracking e descrizione;
- `/exercises/{slug}` mostra descrizione, esecuzione, muscoli e dati in modo coerente;
- `/workout-plans` mostra le schede come card;
- `/workout-plans/new` ha form con campi grandi;
- `/workout-plans/{slug}` mostra esercizi pianificati e form aggiunta esercizio nello stesso stile;
- `/workout-sessions` mostra le sessioni reali come card.

## Verifiche manuali minime Step 8

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make reset-db
make check
```

Serve `make reset-db` perché sono state aggiunte nuove Entity (`ExerciseLoadProfile`, `ExerciseLoadRange`) e quindi cambia lo schema.

Verificare nel browser:

- aprire `/calibrations`;
- avviare la calibrazione di un esercizio peso/reps, ad esempio Panca piana;
- compilare almeno una serie con kg, reps e possibilmente RIR;
- premere “Finalizza calibrazione”;
- tornare su `/calibrations` e verificare che l'esercizio mostri un profilo carichi con i range stimati;
- verificare che esercizi cardio/tempo mostrino “Calibrazione non necessaria”.

Nuovo test unitario aggiunto:

- `LoadEstimationServiceTest`, per bloccare la formula di stima 1RM e la generazione dei quattro range iniziali.

## Verifiche manuali minime Step 11

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make check
```

Se si parte da database pulito o se mancano le fixture:

```powershell
make reset-db
```

Verificare nel browser:

- aprire una scheda;
- aggiungere un esercizio peso/ripetizioni con progressione `Trenino Invictus`;
- avviare l'allenamento;
- verificare che l'esercizio generi 4 serie: due 8-10 e due 10-12;
- se l'esercizio è stato calibrato, verificare che compaia il carico suggerito;
- se l'esercizio non è stato calibrato, verificare che il diario mostri chiaramente `Non disponibile` senza bloccare l'inserimento manuale.

Nuovi test unitari:

- `TreninoInvictusProgressionServiceTest` verifica gli step del Trenino;
- `ExerciseLoadProfileSuggestionTest` verifica la scelta del carico per range;
- `WorkoutSessionFactoryTest` verifica che una scheda con Trenino generi lo step 1 nel diario.

## Verifiche manuali minime Step 12

Dopo aver applicato lo zip:

```powershell
composer dump-autoload
make check
```

Verificare nel browser:

- creare o usare una scheda con un esercizio `Trenino Invictus`;
- avviare l'allenamento e compilare tutte le serie dentro il range con RIR 1-2;
- chiudere l'allenamento e controllare che il riquadro Trenino proponga l'avanzamento allo step successivo;
- avviare una nuova sessione dalla stessa scheda e verificare che lo step generato non sia più fisso a 1;
- ripetere il test con una serie sotto range o saltata e verificare che il sistema proponga di ripetere lo step.

Nuovo test unitario:

- `TreninoExerciseEvaluationServiceTest`, che verifica avanzamento, ripetizione e completamento ciclo.

## Verifica manuale Step 31A

Controllare i form CRUD principali lasciando vuoti o duplicando i campi obbligatori: Attrezzature, Esercizi, Schede e Modifica sessione diario. Il risultato atteso è che il flash resti visibile in alto, ma il campo specifico venga evidenziato con errore vicino al controllo.

## Verifica manuale Step 31B

Nei form Attrezzature, Esercizi, Schede e Modifica sessione diario, inserire valori validi in più campi e lasciare apposta un campo obbligatorio vuoto o duplicare lo slug. Dopo l'errore, i valori digitati devono restare nei campi, il campo problematico deve essere evidenziato e il form non deve tornare ai valori precedenti.

## Verifica manuale Step 31C

Aprire le pagine dettaglio di Attrezzature, Esercizi, Schede e Diario, poi premere un’azione distruttiva. La modale deve mostrare titolo coerente, nome dell’elemento e pulsante finale con etichetta specifica (`Elimina` o `Rimuovi`). Annulla deve chiudere la modale senza inviare il form.

## Verifica manuale Step 31D

Nel dettaglio scheda, provare ad aggiungere o modificare righe esercizio con combinazioni non valide: Rep min maggiore di Rep max, Trenino su esercizio non Peso + reps, Cardio su esercizio non cardio, A tempo senza durata, serie fisse senza serie o Rep min. Il sistema deve bloccare il salvataggio, mostrare flash di errore e riportare al form corretto.

## Verifica manuale Step 31E

Aprire i form Nuova attrezzatura, Nuovo esercizio e Nuova scheda. Digitando il nome con slug vuoto, lo slug deve compilarsi automaticamente in formato URL. Se si modifica lo slug a mano, successive modifiche del nome non devono sovrascriverlo.

## Verifica manuale Step 32A

Aprire `/statistics` dopo avere registrato alcune serie nel diario. Verificare che le card riepilogative mostrino valori coerenti, che la sezione per esercizio ordini per volume e che le ultime serie rimandino al dettaglio sessione corretto. Con diario vuoto deve comparire lo stato vuoto con link al diario.

## Verifica manuale Step 32B

Aprire Statistiche e premere `Storico esercizio` su un esercizio con dati. La pagina deve mostrare riepilogo, card per sessione e tabella completa delle serie. I pulsanti `Apri sessione` devono rimandare al diario corretto. Per un esercizio senza dati deve apparire lo stato vuoto senza errori.

## Verifica manuale Step 32C

Aprire `/statistics` e applicare filtri per data inizio, data fine e tipo sessione. Verificare che riepilogo, progressione per esercizio e ultime serie cambino coerentemente. Poi aprire `Storico esercizio`: i filtri devono restare applicati e il pulsante Statistiche deve tornare alla lista mantenendo gli stessi parametri.
