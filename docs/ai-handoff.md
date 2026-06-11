# AI Handoff — FitnessTracker

## Stato corrente

Progetto web Symfony 8.x in PHP, con database SQLite in sviluppo e Tabler via CDN. Per ora non viene implementato un login reale: l'app usa un utente automatico `Utente demo`, recuperato tramite `CurrentUserProvider`.

Il primo step fissa le fondamenta del dominio:

- utente automatico;
- profilo palestra;
- archivio attrezzature/macchine;
- disponibilità delle attrezzature nella palestra;
- archivio esercizi;
- modalità di tracciamento esercizi, inclusi carichi/reps, corpo libero, tempo e cardio;
- layout base Tabler CDN;
- fixture iniziali;
- test unitari minimi.

## Decisioni tecniche

Lo stack scelto è PHP 8.5.x, Symfony 8.x, Doctrine ORM, Doctrine Migrations, Doctrine Fixtures, Twig e Tabler tramite CDN. SQLite è usato in sviluppo tramite file `var/data/app.db`. Con SQLite non si usa `doctrine:database:create`: il file viene creato dalle migration quando necessario.

Il progetto deve sempre essere accompagnato da documentazione aggiornata. Ogni modifica importante deve lasciare traccia in `docs/roadmap.md`, `docs/decisions.md`, `docs/todo.md` o in file specifici che verranno creati più avanti.

## Dominio fissato nello step corrente

La differenza tra `Equipment` ed `Exercise` è centrale. Una macchina o attrezzatura può essere disponibile o non disponibile nella palestra, mentre un esercizio può richiederla come attrezzatura principale.

La scheda di allenamento non è ancora implementata, ma la separazione prevista sarà:

- `WorkoutPlan`: scheda prevista e riutilizzabile;
- `WorkoutSession`: allenamento reale svolto;
- `ExerciseSession`: esercizio reale dentro una sessione;
- `SetLog`: serie reale con dati tecnici e soggettivi.

La calibrazione iniziale sarà una sessione speciale, non una semplice form. Userà lo stesso diario delle serie reali e produrrà un profilo carichi per esercizio.

## Stato implementato

File principali aggiunti/modificati:

- `src/Entity/AppUser.php`
- `src/Entity/GymProfile.php`
- `src/Entity/Equipment.php`
- `src/Entity/GymEquipment.php`
- `src/Entity/Exercise.php`
- `src/Enum/EquipmentType.php`
- `src/Enum/ExerciseTrackingMode.php`
- `src/Enum/ExerciseType.php`
- `src/Repository/AppUserRepository.php`
- `src/Service/CurrentUserProvider.php`
- `src/Controller/DashboardController.php`
- `src/DataFixtures/AppFixtures.php`
- `migrations/Version20260611191000.php`
- `templates/base.html.twig`
- `templates/dashboard/index.html.twig`
- `tests/Unit/ExerciseTest.php`
- `tests/Unit/EquipmentTest.php`

## Prossimo step consigliato

Il prossimo step dovrebbe implementare le pagine di consultazione e gestione base:

1. lista attrezzature;
2. dettaglio attrezzatura;
3. toggle disponibilità attrezzatura nella palestra;
4. lista esercizi;
5. dettaglio esercizio;
6. filtro esercizi disponibili in base alla palestra.

Solo dopo conviene passare alle schede di allenamento.

## Step 2 - Archivio palestra/esercizi navigabile

Aggiunte pagine operative minime per consultare attrezzature, esercizi e configurazione della palestra. La palestra usa ancora l'utente automatico e i dati caricati dalle fixture. La disponibilità di una macchina/attrezzo può essere attivata o disattivata da `/gym/equipment`; il filtro `/exercises?available=1` mostra solo gli esercizi compatibili con le attrezzature disponibili.

Sono stati aggiunti i servizi `GymProfileProvider` e `AvailableExerciseFilter`. Il primo centralizza il recupero della palestra corrente; il secondo isola la logica che decide quali esercizi sono disponibili in base agli slug delle attrezzature attive. Questa logica dovrà essere riutilizzata più avanti per consigli, schede e sostituzioni esercizio.

## Step 3 - Schede di allenamento

Introdotta la pianificazione delle schede con `WorkoutPlan` e `WorkoutPlanExercise`. La scheda è ancora distinta dal diario reale: in questo step si definisce cosa si vorrebbe fare, non ciò che è stato effettivamente svolto.

Le nuove route operative sono:

- `/workout-plans`: elenco schede;
- `/workout-plans/new`: creazione scheda;
- `/workout-plans/{slug}`: dettaglio scheda e aggiunta esercizi;
- `/workout-plans/{slug}/exercises`: aggiunta esercizio alla scheda;
- `/workout-plans/{slug}/exercises/{itemId}/remove`: rimozione esercizio;
- `/workout-plans/{slug}/toggle-active`: attiva/disattiva scheda.

Sono stati aggiunti `WorkoutGoal` e `ProgressionType`. La progressione è assegnata al singolo esercizio dentro la scheda, così il Trenino Invictus potrà convivere con esercizi fissi, cardio, corpo libero o manuali. Le fixture ora caricano due schede iniziali di esempio.

Nota SQLite: in questa fase il modello dati cambia spesso. Il Makefile è stato aggiornato per usare `doctrine:schema:create` in `reset-db`, invece di obbligare migration non ancora stabili. Le migration restano nel progetto, ma per sviluppo rapido la procedura consigliata è `make reset-db` quando cambiano le Entity.

## Step 4 - Diario allenamenti reali

Introdotta la prima versione del diario allenamenti. Da una scheda è ora possibile avviare una sessione reale: gli esercizi pianificati vengono copiati dentro una `WorkoutSession`, ogni esercizio diventa una `ExerciseSession` e le serie pianificate diventano righe `SetLog` compilabili.

Nuove route operative:

- `/workout-sessions`: elenco allenamenti reali;
- `/workout-sessions/start/{slug}`: avvio allenamento da scheda;
- `/workout-sessions/{id}`: dettaglio sessione e registrazione serie;
- `/workout-sessions/{sessionId}/sets/{setId}`: aggiornamento dati reali di una serie;
- `/workout-sessions/{id}/complete`: chiusura sessione come completata o parziale.

I dati reali registrabili nello step sono peso, reps, durata, distanza, livello/resistenza, RIR, cedimento, percezione carico, percezione sforzo, recupero effettivo e note. La correzione automatica dei carichi non è ancora implementata: arriverà con calibrazione e Trenino.

## Step 5 - Consolidamento diario reale

Consolidata la registrazione degli allenamenti reali prima di passare alla calibrazione. Le serie possono ora essere marcate esplicitamente come saltate con motivazione. Anche un intero esercizio della sessione può essere saltato: in quel caso tutte le sue serie vengono chiuse come saltate e l'esercizio passa allo stato `Skipped`.

Aggiunto `WorkoutSessionSummary`, servizio descrittivo che calcola riepilogo sessione: serie pianificate, completate, saltate, rimanenti, reps totali, volume carico kg x reps, durata totale e ripetizioni in riserva medie. Il dettaglio sessione mostra ora card riepilogative e distingue serie completate, saltate e ancora aperte.

Nuove route operative:

- `/workout-sessions/{sessionId}/sets/{setId}/skip`: marca una serie come saltata;
- `/workout-sessions/{sessionId}/exercises/{exerciseSessionId}/skip`: marca un esercizio come saltato.

Nota dominio: una serie saltata viene considerata chiusa per il completamento della sessione, ma non contribuisce a volume, reps o ripetizioni in riserva medie. Se una sessione viene chiusa con almeno una serie saltata, lo stato finale resta `Partial`, non `Completed`.

## Step 6 - UI mobile-first registrazione serie

Sistemata la pagina di dettaglio sessione prima di procedere con calibrazione e Trenino. La tabella larga delle serie è stata sostituita da card responsive per serie, pensate per l'inserimento da smartphone in palestra.

La pagina salva tutti i dati della serie con etichette esplicite e sempre visibili: peso, ripetizioni, secondi, distanza, livello/resistenza macchina, recupero effettivo, ripetizioni in riserva, percezione del carico, percezione dello sforzo, cedimento e note. I campi sono raggruppati per sezioni leggibili senza blocchi espandibili.

Aggiunti metodi descrittivi a `ExerciseTrackingMode` per evitare logica fragile nel template: `usesWeight`, `usesReps`, `usesDuration`, `usesDistance`, `usesResistanceLevel`, `usesRirByDefault`, `usesPerceivedLoadByDefault`, `usesPerceivedEffortByDefault` e `usesFailureByDefault`.

## Step 6B - Correzione UI serie sempre visibile

La prima versione mobile-first usava un blocco `details` chiuso per i dati extra. Questa scelta è stata scartata: durante l'uso reale in palestra da smartphone tutti i dati della serie devono essere visibili senza dover aprire sezioni nascoste.

La pagina `/workout-sessions/{id}` ora mostra per ogni serie un riquadro esplicito “Suggerimento” con carico suggerito, target reps/durata e recupero previsto. Quando il carico suggerito non è disponibile, la UI lo dichiara chiaramente con “Non disponibile” e spiega che manca ancora un profilo carichi o una progressione in grado di calcolarlo. Questo evita l'ambiguità: in questa fase l'app registra il diario, ma non propone ancora carichi finché non saranno implementati calibrazione iniziale e Trenino.

Tutti i campi della serie sono ora sempre visibili e raggruppati in tre sezioni: `Prestazione reale` (`Peso (kg)`, `Ripetizioni`, `Secondi`, `Distanza (metri)`, `Livello / resistenza macchina`, `Recupero effettivo (secondi)`), `Fatica` (`Ripetizioni in riserva (RIR)`, `Percezione del carico`, `Percezione dello sforzo`, `Cedimento`) e `Note`. Non ci sono più blocchi espandibili nascosti.

## Step 7 - Stile UI uniforme mobile-first

Lo stile validato nel diario allenamento è stato assunto come riferimento generale dell'interfaccia. Le pagine principali non usano più tabelle larghe dove non sono necessarie: dashboard, attrezzature, palestra, esercizi, schede e diario elenco sono state riallineate con card leggibili, sezioni esplicite, badge di stato, blocchi informativi e pulsanti grandi.

Il foglio stile comune è stato spostato in `templates/base.html.twig`, così le classi riutilizzabili (`app-mobile-card`, `app-info-grid`, `app-section`, `app-field-grid`, `app-action-grid`) sono disponibili in tutte le pagine. La pagina `/workout-sessions/{id}` mantiene ancora alcune classi specifiche per le serie, ma il linguaggio visivo è ora coerente con il resto dell'app.

Nessuna Entity è stata modificata in questo step. Non servono migration né reset database: dopo l'applicazione basta `composer dump-autoload` e `make check`.

## Step 8 - Calibrazione iniziale dei carichi

Avviata la Milestone calibrazione. È stata introdotta la pagina `/calibrations`, raggiungibile dal menu principale, che mostra gli esercizi e lo stato del relativo profilo carichi. Per gli esercizi con tracking `Peso + reps` è possibile avviare una sessione di calibrazione dedicata.

La calibrazione riutilizza il diario reale: viene creata una `WorkoutSession` con `sessionType = Calibration`, un solo `ExerciseSession` e quattro `SetLog` test con target 8-10 reps. L'utente registra kg, reps, ripetizioni in riserva, percezione del carico, percezione dello sforzo e note nella stessa UI mobile-first già validata per il diario.

Aggiunte le Entity `ExerciseLoadProfile` e `ExerciseLoadRange`. Il profilo salva fonte, confidenza, stima 1RM e range calcolati per 10-12, 8-10, 6-8 e 4-6. Aggiunti gli enum `LoadProfileSource` e `LoadProfileConfidence`.

Aggiunto `LoadEstimationService`, che stima l'1RM con `peso * (1 + (reps + RIR) / 30)`, applica un fattore conservativo iniziale e arrotonda al `defaultIncrementKg` dell'esercizio. Il servizio seleziona la miglior stima tra le serie della sessione di calibrazione e genera i range iniziali. Se manca il RIR, usa RIR 2 come fallback prudente e abbassa la confidenza.

Aggiunto `CalibrationSessionFactory` per creare sessioni test progressive. Il dettaglio sessione ora distingue le sessioni di calibrazione e mostra il pulsante “Finalizza calibrazione”, che crea il profilo carichi e chiude la sessione.


## Aggiornamento UI diario - etichette esplicite

Nel diario allenamenti le etichette dei campi devono evitare abbreviazioni quando lo spazio lo consente. `Rec. sec.` diventa `Recupero effettivo (secondi)`, `Sec.` diventa `Secondi`, `Reps` diventa `Ripetizioni`. Il campo `Livello` è esplicitato come `Livello / resistenza macchina`, da usare per cyclette, vogatore, macchine cardio o attrezzi con livelli numerici. Il campo RIR resta tecnicamente importante, ma viene presentato come `Ripetizioni in riserva (RIR)` con combobox guidata, perché l’utente non deve ricordare il significato dell’acronimo durante l’allenamento.

## Step 11 - Trenino Invictus collegato al diario

Aggiunto il primo collegamento operativo tra `ProgressionType::TreninoInvictus` e la generazione delle serie reali. Quando un esercizio dentro una scheda viene marcato come Trenino Invictus, `WorkoutSessionFactory` non usa più la prescrizione manuale `plannedSets/plannedRepMin/plannedRepMax`, ma genera lo step 1 del Trenino: 2 serie 8-10 e 2 serie 10-12 con recupero previsto 150 secondi.

La progressione completa è centralizzata in `TreninoInvictusProgressionService`, che contiene i 6 step del modello. Per ora lo step corrente è fisso a 1 quando si avvia una nuova sessione da scheda. Il prossimo passaggio sarà calcolare lo step successivo leggendo lo storico dell'esercizio, le sessioni saltate e l'esito della seduta precedente.

Aggiunto anche `LoadSuggestionService`, che recupera il profilo carichi più recente dell'utente per un esercizio e prova a valorizzare `SetLog.targetWeightKg` in base al range reps della serie. Se esiste una calibrazione per quell'esercizio, il diario può quindi mostrare il carico suggerito anche nelle serie generate dal Trenino.

## Step 12 - Avanzamento Trenino derivato dallo storico

Il Trenino Invictus non parte più sempre dallo step 1. È stato aggiunto `TreninoStepResolver`, che cerca l'ultima `ExerciseSession` chiusa per lo stesso `WorkoutPlanExercise` e calcola lo step successivo tramite `TreninoExerciseEvaluationService`.

La valutazione è prudente: si avanza solo se tutte le serie sono registrate, nessuna è saltata, nessuna è sotto il minimo del range e nessuna risulta troppo pesante già al minimo del range. In caso di serie mancanti, saltate, sotto range, a cedimento al minimo del range o con percezione `Troppo pesante`/`Cedimento`, lo step viene ripetuto. Lo step 6 completato correttamente chiude il ciclo e propone il ritorno allo step 1.

La pagina diario mostra ora, per ogni esercizio Trenino, un riquadro con lo step corrente, la decisione stimata e il prossimo step. Alla chiusura dell'allenamento viene anche mostrato un messaggio flash riepilogativo per gli esercizi Trenino presenti nella sessione.

## Step 14 - Correzione carico operativa per singola serie

Aggiunto `SetLoadAdjustmentService`, che valuta ogni `SetLog` con range reps, peso reale, ripetizioni, RIR, cedimento e percezione del carico. La pagina diario mostra ora un riquadro “Correzione dopo questa serie” con decisione, motivazione e, quando possibile, peso consigliato per la prossima serie. La modifica non cambia lo schema database.

## Step 15 - Propagazione del carico suggerito alla prossima serie

La correzione carico intra-sessione ora non resta solo informativa: dopo il salvataggio di una serie, `SetLoadAdjustmentService::applySuggestedWeightToNextOpenCompatibleSet()` valuta la serie appena completata e, se possibile, aggiorna il `targetWeightKg` della prossima serie ancora aperta dello stesso esercizio e con lo stesso range di ripetizioni. Questo evita di applicare un peso da 8-10 reps a un blocco successivo 10-12 reps del Trenino.

La modifica non cambia lo schema database. Il valore aggiornato è ancora il normale carico target della serie successiva.

## Step 16 - Cedimento gestito solo tramite RIR

La checkbox separata `Cedimento` è stata rimossa dalla UI del diario. Da ora il cedimento viene espresso selezionando `0 - Cedimento / non ne avevo più` nella combo `Ripetizioni in riserva (RIR)`. Il controller deriva `SetLog::reachedFailure` da `RIR <= 0`, così la logica interna continua a funzionare senza duplicare l'input utente. `PerceivedLoad::Failure` resta nell'enum per compatibilità con dati già salvati, ma non viene più mostrato nelle select.

## Step 17 - Carico suggerito visuale dalla serie precedente

Corretto un caso di incoerenza UI: la correzione della serie appena registrata indicava un peso per la prossima serie, ma il box `Carico suggerito` della serie successiva poteva restare `Non disponibile`. Ora `SetLoadAdjustmentService` produce anche `SetWeightSuggestion`, usato dal template per mostrare il carico target ufficiale oppure, se manca, un suggerimento derivato dalla serie precedente compatibile.

## Step 18 - Copia rapida del carico suggerito

Aggiunto un pulsante `Usa questo peso` nel box `Carico suggerito` del diario. Il pulsante copia il valore suggerito nel campo `Peso (kg)` della stessa serie, senza salvare automaticamente. Questo mantiene il controllo manuale durante l'allenamento, ma riduce errori e tocchi inutili da smartphone.

## Step 19 - Copia rapida del recupero previsto

Aggiunto nel diario il pulsante `Usa questo recupero` accanto al box `Recupero previsto`. Il valore viene copiato nel campo `Recupero effettivo (secondi)` della stessa serie, senza salvare automaticamente. La logica è analoga al pulsante `Usa questo peso` e mantiene il controllo manuale durante l'allenamento.

## Step 20 - Prossima serie da compilare

Aggiunta evidenziazione della prima serie ancora aperta nel diario. Il controller passa `nextOpenSetId` al template, che mostra un alert con link rapido e un badge `Prossima da compilare` sulla card della serie. Nello stesso intervento è stata riallineata la vista passando di nuovo `setWeightSuggestions`, richiesto dal template per il box `Carico suggerito`.

## Step 21 - Ritorno automatico alla prossima serie aperta

Dopo il salvataggio o il salto di una serie, il redirect della pagina allenamento punta ora alla prima serie ancora aperta. Se non ci sono più serie aperte, torna alla serie appena gestita. Aggiunto anche highlight `:target` per rendere visibile la card raggiunta dopo il redirect.

## Step 22 - Modalità Solo serie aperte

Aggiunta nel diario la modalità `Solo serie aperte`. Il template marca ogni serie con `data-open-set` e ogni esercizio con `data-open-set-count`; tramite JavaScript viene applicata la classe `show-open-sets-only` sull'elemento `html`, nascondendo le serie già registrate o saltate e gli esercizi senza serie aperte. La preferenza viene salvata in `localStorage`, così resta attiva durante l'allenamento.

## Step 23 - CRUD Attrezzature

Implementato il primo CRUD manuale: `EquipmentController` ora gestisce index, show, new, edit e delete. Aggiunto template `templates/equipment/form.html.twig` e aggiornate lista/dettaglio con pulsanti di gestione. La logica valida nome, descrizione, tipo e slug univoco; lo slug può essere lasciato vuoto e viene generato dal nome. Nessuna modifica allo schema database.

## Step 24 - CRUD Esercizi

Implementato il CRUD manuale degli esercizi. `ExerciseController` ora gestisce index, show, new, edit e delete; il form permette di configurare nome, slug, descrizione, istruzioni, muscoli principali/secondari, modalità di registrazione, tipo esercizio, attrezzatura principale, incremento default, note attrezzatura, immagine e flag fondamentale. L'eliminazione intercetta eventuali vincoli FK e blocca la cancellazione se l'esercizio è già usato nello storico. Nessuna modifica allo schema database.

## Step 25 - CRUD Schede

Implementato il CRUD completo delle schede. `WorkoutPlanController` ora gestisce creazione, modifica, eliminazione, attivazione/disattivazione, aggiunta esercizi, modifica righe esercizio, rimozione righe e riordino su/giù. Aggiunto template riutilizzabile `templates/workout_plan/form.html.twig`; la pagina dettaglio scheda permette di modificare direttamente prescrizione, progressione, serie, reps, durata, recupero e note di ogni riga. Nessuna modifica allo schema database.

## Step 26 - CRUD Diario

Implementato il CRUD base del Diario. `WorkoutSessionController` ora permette di modificare i dati generali di una sessione (`sessionDate`, `startedAt`, `endedAt`, `status`, `notes`) e di eliminare una sessione completa. L'eliminazione rimuove anche esercizi e serie della sessione, ma non elimina schede, esercizi o attrezzature di catalogo. Aggiunto `templates/workout_session/form.html.twig`; lista e dettaglio diario hanno ora pulsanti Modifica/Elimina. Nessuna modifica allo schema database.

## Step 27 - Duplicazione Schede

Aggiunta azione `Duplica` sulle schede. La copia mantiene dati generali, obiettivo, giorno consigliato e tutte le righe esercizio con ordine, progressione, serie, reps, durata, recupero e note. La nuova scheda viene creata disattivata, con nome `... - copia` e slug univoco, così l'utente può controllarla prima di usarla.

## Step 28 - Fix overflow pulsanti card schede

Corretto overflow delle azioni nelle card, riscontrato nella lista `Schede allenamento` dopo l'aggiunta di più pulsanti (`Apri scheda`, `Modifica`, `Duplica`, `Disattiva`). Lo stile globale `.app-action-grid` ora consente wrapping dei pulsanti e rende link/form flessibili, evitando che le azioni escano dai bordi della card.

## Step 29 - Azioni distruttive separate nelle card

Aggiunto stile globale `app-danger-action` per separare le azioni distruttive dalle azioni principali nelle card. Le form di eliminazione/rimozione ora occupano una riga dedicata con separatore tratteggiato, evitando sia compressione dei pulsanti sia ambiguità tra azioni normali e distruttive.

## Step 30 - Conferma modale per azioni distruttive

Sostituiti i `confirm()` JavaScript del browser con una modale Tabler/Bootstrap riutilizzabile definita in `templates/base.html.twig`. Le form distruttive usano ora `data-confirm-message`, intercettato da uno script globale che mostra la modale e invia il form solo dopo conferma esplicita. Coinvolte eliminazioni di attrezzature, esercizi, schede, sessioni e rimozione righe esercizio da scheda.

## Step 31A - Validazioni visive CRUD

Avviato consolidamento CRUD. I controller manuali di Attrezzature, Esercizi, Schede e Diario ora passano `formErrors` ai template. Gli errori non sono più solo flash generici: i campi principali vengono marcati con `is-invalid` e mostrano `invalid-feedback` vicino al campo. Coinvolti: nome/slug/descrizione/tipo attrezzatura; nome/slug/descrizione/tracking/tipo/attrezzatura/incremento esercizio; nome/slug scheda; data/inizio/fine/stato sessione. Nessuna modifica allo schema database.

## Step 31B - Conservazione valori form dopo errore

Completato secondo passaggio del consolidamento CRUD. I controller di Attrezzature, Esercizi, Schede e Diario ora passano ai template `formData` e `formSubmitted`, così i valori inseriti dall'utente restano visibili quando la validazione fallisce. I template usano `formData` come priorità e ricadono sull'entità solo in GET o quando il campo non è presente. Gestiti anche checkbox e select senza sovrascrivere lo stato precedente in caso di submit fallito.

## Step 31C - Conferme distruttive specifiche

Raffinate le modali di conferma per le azioni distruttive. `templates/base.html.twig` ora supporta `data-confirm-title` e `data-confirm-submit-label`, oltre al messaggio. Le form distruttive includono messaggi specifici con il nome dell’elemento coinvolto: attrezzatura, esercizio, riga esercizio nella scheda, scheda e sessione diario. Nessuna modifica PHP o database.

## Step 31D - Validazioni righe scheda

Aggiunte validazioni alle righe esercizio delle schede in `WorkoutPlanController`. Prima di aggiungere o modificare una riga, il controller controlla coerenza tra progressione, tracking mode e campi prescrizione. Esempi: Rep min non può essere maggiore di Rep max; valori numerici devono essere positivi; Trenino Invictus è consentito solo su esercizi Peso + reps; A tempo richiede esercizi con durata e durata impostata; Cardio richiede esercizi Cardio macchina o Tempo + distanza; serie fisse/manuali su esercizi a ripetizioni richiedono serie e Rep min. Il form di aggiunta ha ora anchor `add-exercise` e note operative sulle regole.

## Step 31E - Generazione automatica slug

Aggiunto helper JavaScript globale in `templates/base.html.twig` per compilare automaticamente lo slug dal nome nei form principali. I campi nome di Attrezzature, Esercizi e Schede usano `data-slug-source` verso il rispettivo campo slug. La generazione avviene solo finché lo slug è vuoto: appena l’utente modifica lo slug manualmente, lo script non lo sovrascrive più. Nessuna modifica PHP o database.

## Step 32A - Statistiche base

Aggiunta prima pagina statistiche `/statistics` con `StatisticsController` e template `templates/statistics/index.html.twig`. La pagina aggrega i dati già presenti nel diario senza nuove tabelle: sessioni con serie registrate, esercizi con dati, serie completate, ripetizioni totali, volume kg x reps e RIR medio. Include una sezione per esercizio con sessioni, serie, volume, ripetizioni, miglior peso, ultimo peso, RIR medio e ultima sessione, più una tabella delle ultime 20 serie registrate. Aggiunto link Statistiche nel menu principale.

## Step 32B - Storico dettagliato esercizio

Aggiunta pagina dettaglio storico per singolo esercizio. La nuova rotta `app_statistics_exercise` (`/statistics/exercises/{slug}`) legge lo storico dell’utente corrente da `WorkoutSession`, `ExerciseSession` e `SetLog`, senza nuove tabelle. La pagina mostra riepilogo dell’esercizio, aggregazione per sessione e tabella completa di tutte le serie registrate con previsto, registrato, volume, RIR, percezioni, recupero e note. La pagina Statistiche ora collega ogni esercizio allo storico dedicato e mantiene anche il link alla scheda esercizio.

## Step 32C - Filtri statistiche

Aggiunti filtri a Statistiche e Storico esercizio. `StatisticsController` ora riceve `Request`, legge `dateFrom`, `dateTo` e `sessionType`, filtra le `WorkoutSession` dell’utente corrente prima di aggregare i dati e passa `filters`, `filterQuery` e `sessionTypes` ai template. I filtri sono disponibili sia su `/statistics` sia su `/statistics/exercises/{slug}` e vengono mantenuti quando si passa dalla lista esercizi allo storico del singolo esercizio.

## Step 32D - Andamento visuale esercizio

Esteso lo storico del singolo esercizio con una sezione `Andamento recente`. La pagina `/statistics/exercises/{slug}` mostra ora un confronto visuale delle ultime sessioni filtrate per quell'esercizio, dalla più vecchia alla più recente, usando due mini-grafici CSS senza librerie esterne: peso migliore per sessione e volume per sessione.

La logica di preparazione dei dati è stata isolata in `ExerciseTrendBuilder`, così il controller resta più leggibile e la normalizzazione del trend è testabile separatamente. Il servizio prende i riepiloghi per sessione già calcolati dal controller, limita l'analisi alle ultime 8 sessioni, normalizza le percentuali delle barre e produce un confronto rispetto alla sessione precedente.

Non sono state aggiunte tabelle e non serve migration. Il blocco statistiche rimane basato sui dati già presenti nel diario.
