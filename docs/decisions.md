# Decisioni progettuali

## Login automatico

Per la MVP non viene implementata autenticazione reale. Esiste un solo utente automatico, creato dalle fixture. Tutto il codice deve recuperare l'utente tramite `CurrentUserProvider`, così in futuro si potrà sostituire il provider con l'utente autenticato senza riscrivere il dominio.

## Tabler via CDN

Tabler viene usato via CDN nel layout Twig principale. Questo evita NPM, bundler e pipeline frontend nelle prime milestone. Se in futuro servirà una gestione asset più controllata, si valuterà AssetMapper o un sistema frontend dedicato.

## SQLite in sviluppo

Il database di sviluppo è SQLite in `var/data/app.db`. Con SQLite non si usa `doctrine:database:create`; si crea la cartella `var/data` e si applicano le migration. Il file `.db` non deve essere inserito nello zip di handoff.

## Scheda prevista e sessione reale sono separate

La scheda è un piano riutilizzabile. La sessione reale è la verità storica di ciò che è stato fatto. Cambiare una scheda non deve modificare retroattivamente le sessioni registrate.

## Calibrazione come sessione dedicata

La calibrazione iniziale non è una semplice maschera. È una sessione di allenamento speciale, con serie reali e dati di fatica. Alla fine produce un profilo carichi per esercizio.

## Tracking mode per esercizio

Non tutti gli esercizi usano peso e ripetizioni. Ogni esercizio ha un `trackingMode`:

- `weight_reps`;
- `bodyweight_reps`;
- `reps_only`;
- `time`;
- `time_distance`;
- `cardio_machine`;
- `isometric_time`;
- `free_notes`.

Questa decisione evita di forzare cardio, addominali, plank o corpo libero nel modello kg x reps.

## Trenino applicato per esercizio

La progressione Trenino Invictus sarà applicabile a un singolo esercizio dentro una scheda, non obbligatoriamente a tutta la scheda.

## Decisione Step 2 - filtro esercizi disponibili

La disponibilità di un esercizio nella MVP è calcolata sull'attrezzatura principale (`Exercise.defaultEquipment`). Se un esercizio non ha attrezzatura principale viene considerato disponibile. Le attrezzature secondarie sono per ora solo testo descrittivo (`secondaryEquipmentNotes`) e non bloccano il filtro. Questa scelta mantiene semplice la MVP e potrà evolvere verso una relazione many-to-many tra esercizi e attrezzature.

## Decisione Step 3 - schema create durante la fase iniziale

La migration iniziale scritta manualmente non era perfettamente allineata allo schema Doctrine generato su SQLite. Per evitare attrito mentre il dominio cambia rapidamente, `make reset-db` ora elimina `var/data/app.db`, crea lo schema direttamente dalle Entity con `doctrine:schema:create`, carica le fixture, valida lo schema ed esegue i test. Quando il dominio sarà più stabile, verrà rigenerata una migration pulita con `make:migration`.

## Decisione Step 3 - scheda pianificata minima

`WorkoutPlan` contiene il piano riutilizzabile. `WorkoutPlanExercise` contiene esercizio, ordine, tipo progressione, serie/reps/durata/recupero e note. I dati reali dell'allenamento non devono essere salvati qui: saranno gestiti da `WorkoutSession`, `ExerciseSession` e `SetLog`.

## Decisione Step 4 - sessione reale generata da scheda

L'avvio allenamento copia la scheda dentro una sessione reale. Questo preserva lo storico: se in futuro la scheda viene modificata, le sessioni già svolte mantengono esercizi, ordine, progressione e serie generate al momento dell'avvio.

## Decisione Step 4 - `SetLog` unico per diversi tracking mode

Per la MVP una singola entità `SetLog` contiene campi opzionali per peso/reps, tempo, distanza, livello/resistenza, RIR e note. Questo evita di creare troppe tabelle troppo presto. Più avanti la UI potrà mostrare solo i campi coerenti con il `trackingMode` dell'esercizio.

## Decisione Step 5 - serie saltate come serie chiuse ma non produttive

Una serie saltata viene considerata chiusa per capire se la sessione può essere archiviata, ma non contribuisce a reps, volume, durata o ripetizioni in riserva medie. Questa distinzione permette di calcolare l'aderenza reale senza inquinare le statistiche prestative.

## Decisione Step 5 - sessione con salti chiusa come parziale

Se tutte le serie sono state compilate o saltate, la sessione può essere chiusa. Tuttavia, se contiene almeno una serie saltata, lo stato finale è `Partial`. Lo stato `Completed` viene riservato alle sessioni in cui tutte le serie pianificate hanno dati reali.

## Decisione Step 6 - diario mobile-first per serie

La registrazione serie deve essere ottimizzata per smartphone. Non si usa più una tabella desktop con molti campi affiancati. Ogni serie viene mostrata come card autonoma, con target, riepilogo registrato, badge di stato e form diviso per sezioni.

Tutti i campi del diario restano disponibili e visibili. Il `trackingMode` potrà in futuro aiutare a mettere in maggiore evidenza i campi più pertinenti, ma non deve nascondere gli altri campi durante l'allenamento.

## Decisione - Nessun campo nascosto nel diario serie

Nel diario allenamenti usato da smartphone, i campi della serie devono restare visibili. I blocchi espandibili tipo `details` non sono adatti perché durante l'allenamento l'utente deve vedere subito tutto ciò che può registrare. La UI può dare priorità visiva ai campi più importanti, ma non deve nascondere peso, ripetizioni, secondi, distanza, livello/resistenza macchina, recupero effettivo, ripetizioni in riserva, percezione carico, percezione sforzo e note.

Il carico suggerito deve essere sempre esplicito: se non esiste, la pagina deve dire “Non disponibile” e indicare che manca calibrazione/progressione. Non deve sembrare un errore o un campo dimenticato.

## Decisione Step 7 - stile UI unico derivato dal diario

Lo stile della pagina diario allenamento è il riferimento di prodotto. Tutte le nuove pagine devono essere progettate mobile-first, con card autonome, sezioni chiare, badge espliciti e pulsanti grandi. Le tabelle vanno usate solo quando portano un vantaggio reale; per smartphone e operatività in palestra si preferiscono card verticali.

Le classi CSS comuni sono dichiarate in `templates/base.html.twig` per evitare duplicazione e per mantenere coerenza visiva tra dashboard, cataloghi, schede, diario, futura calibrazione e progressione Trenino.

## Decisione Step 8 - calibrazione come sessione reale

La calibrazione iniziale non usa un flusso separato dal diario. È una `WorkoutSession` reale di tipo `Calibration`, perché deve essere tracciabile nello storico e perché usa gli stessi campi operativi: kg, reps, ripetizioni in riserva, percezione del carico, percezione dello sforzo e note.

Il primo algoritmo di stima è intenzionalmente semplice e spiegabile. Parte dalla formula Epley adattata al RIR, calcola un 1RM stimato e ricava i pesi per i range centrali del Trenino. In questa fase non cerca precisione assoluta: crea un punto di partenza prudente che verrà poi corretto dal diario e dalla futura progressione Trenino.

Il profilo carichi è versionato nel tempo tramite `validFrom`: ogni nuova calibrazione crea un nuovo `ExerciseLoadProfile`, senza sovrascrivere automaticamente lo storico precedente.


## Aggiornamento UI diario - etichette esplicite

Nel diario allenamenti le etichette dei campi devono evitare abbreviazioni quando lo spazio lo consente. `Rec. sec.` diventa `Recupero effettivo (secondi)`, `Sec.` diventa `Secondi`, `Reps` diventa `Ripetizioni`. Il campo `Livello` è esplicitato come `Livello / resistenza macchina`, da usare per cyclette, vogatore, macchine cardio o attrezzi con livelli numerici. Il campo RIR resta tecnicamente importante, ma viene presentato come `Ripetizioni in riserva (RIR)` con combobox guidata, perché l’utente non deve ricordare il significato dell’acronimo durante l’allenamento.

## Decisione Step 11 - Trenino generato da servizio dedicato

Il Trenino Invictus non viene salvato come righe configurabili nel database nella prima versione. La progressione è codice di dominio stabile dentro `TreninoInvictusProgressionService`, perché il modello ha una struttura nota di 6 step. Questo evita di introdurre tabelle premature per template/step/blocchi e rende più semplice testare il comportamento.

Lo step corrente è inizialmente fissato a 1 alla generazione della sessione. Questa è una scelta temporanea: lo stato reale della progressione dovrà essere calcolato o salvato quando sarà implementata l'analisi di avanzamento/ripetizione dello step.

## Decisione Step 11 - carico suggerito non obbligatorio

`targetWeightKg` resta opzionale. Se non esiste una calibrazione o un profilo carichi compatibile, la serie viene comunque generata e l'utente inserisce il peso reale manualmente. Questa scelta evita di bloccare il diario quando l'app non ha ancora dati sufficienti.

## Decisione Step 12 - avanzamento Trenino prudente e derivato

Per evitare nuove tabelle premature, lo step corrente del Trenino viene inizialmente derivato dallo storico delle sessioni chiuse, non salvato in una tabella di stato separata. La chiave pratica è l'esercizio dentro la scheda (`WorkoutPlanExercise`): in questo modo lo stesso esercizio può avere progressioni diverse in schede diverse.

La regola di avanzamento è volutamente conservativa. Il sistema avanza solo quando lo step è stato chiuso in modo pulito. Se mancano dati o ci sono serie saltate, fallite o troppo tirate, la progressione ripete lo stesso step invece di forzare l'aumento di intensità.

## Cedimento e RIR

Il cedimento non deve essere inserito con una checkbox separata. La fonte dati utente è la combo `Ripetizioni in riserva (RIR)`: valore `0` significa cedimento. Il campo tecnico `reachedFailure` può rimanere nel dominio come dato derivato, utile per valutazioni e retrocompatibilità.

## Gestione dati manuale

La gestione manuale deve essere introdotta progressivamente. Prima Attrezzature, poi Esercizi, poi Schede, infine Diario. Ogni CRUD deve mantenere lo stile mobile-first già usato nel diario, con pulsanti grandi e azioni chiare, perché l'app deve restare utilizzabile anche da smartphone.

## CRUD Esercizi

Gli esercizi vengono gestiti manualmente con un form unico per creazione e modifica. I muscoli sono inseriti come testo separato da virgole e salvati come array JSON. Lo slug può essere generato automaticamente dal nome. L'eliminazione deve essere prudente: se l'esercizio è usato in schede, sessioni o calibrazioni, la cancellazione viene bloccata e l'utente viene invitato a modificarlo.

## CRUD Schede

La scheda è modificabile su due livelli: dati generali tramite pagina dedicata, righe esercizio direttamente dal dettaglio scheda. Le righe possono essere riordinate con azioni esplicite `Sposta su` e `Sposta giù`, più sicure su smartphone rispetto al drag and drop. L'eliminazione della scheda è consentita: le sessioni storiche mantengono i propri dati e perdono solo il riferimento alla scheda.

## CRUD Diario

Il Diario viene gestito a livello di sessione: modifica dei dati generali e cancellazione dell'intera sessione. Le serie restano modificabili nel dettaglio allenamento tramite i form già presenti. L'eliminazione di una sessione è consentita perché riguarda dati storici creati dall'utente e non altera cataloghi o schede.

## Duplicazione Schede

La copia di una scheda deve essere disattivata per impostazione predefinita. Questo evita di avere due schede attive quasi identiche senza controllo. La duplicazione copia solo la struttura della scheda e le prescrizioni, non lo storico del diario.

## Decisione Step 32D - grafici leggeri senza librerie esterne

La prima visualizzazione dell'andamento esercizio non introduce Chart.js o altre librerie grafiche. Le statistiche devono restare semplici, veloci e coerenti con la UI mobile-first già esistente. I mini-grafici sono barre HTML/CSS calcolate da dati normalizzati lato PHP tramite `ExerciseTrendBuilder`. Se in futuro serviranno grafici interattivi più complessi, questa scelta potrà essere rivista senza modificare il modello dati.

## Decisione Step 32E - miglior set stimato come metrica derivata

Il miglior set stimato non viene salvato nel database. È una metrica derivata dai dati già presenti nel diario, calcolata al momento nelle statistiche. La formula scelta è Epley, con il RIR trattato come ripetizioni potenziali residue e con limiti prudenziali interni. Questa scelta evita di complicare lo schema dati e permette di cambiare formula in futuro senza migration o pulizie storiche.

## Decisione Step 32F - aggregazione settimanale derivata

L'aggregazione settimanale dello storico esercizio non viene salvata nel database. È una vista derivata dai dati del diario e dai riepiloghi sessione già calcolati per la pagina `/statistics/exercises/{slug}`. Le settimane usano il calendario ISO, con inizio lunedì e fine domenica, così il comportamento resta prevedibile anche quando l'anno cambia.

Il RIR medio settimanale è ponderato sul numero di serie della sessione, non calcolato come semplice media delle medie sessione. Questa scelta evita che una sessione con poche serie pesi quanto una sessione completa.

## Decisione Step 33A - menu top raggruppato per area funzionale

La barra principale deve rimanere una navigazione di primo livello, non l'elenco completo di tutte le pagine operative. Per questo le voci di catalogo e configurazione sono state raggruppate sotto `Palestra`, mentre le voci legate alla pratica dell'allenamento sono state raggruppate sotto `Allenamento`. Dashboard e Statistiche restano link diretti perché sono viste trasversali e frequentemente consultate.

La scelta evita soluzioni fragili come ridurre il font, lasciare andare il menu a capo o aggiungere una sidebar prematura. Se il numero di funzioni crescerà molto, la stessa logica potrà essere estesa con altri gruppi senza cambiare le rotte esistenti.

## Decisione Step 34A - 1RM stimato prudente

Il `1RM stimato` deve rimanere una metrica derivata e prudente, non un massimale reale. La formula Epley continua a essere usata perché semplice e leggibile, ma viene applicata solo entro limiti sensati: fino a 15 ripetizioni equivalenti la stima è considerata standard, oltre 15 e fino a 20 viene mostrata come indicativa, oltre 20 non alimenta il miglior valore.

Le ripetizioni equivalenti includono il RIR positivo, limitato a un massimo di 5 ripetizioni aggiuntive. Questa scelta evita che serie metaboliche o metodi ad altissime ripetizioni, come lavori da 70-100 ripetizioni, falsino le statistiche di forza. Le serie escluse restano comunque visibili nel diario e nelle tabelle, ma non concorrono al miglior `1RM stimato`.

## Decisione Step 34B - qualità visibile delle stime

La qualità del `1RM stimato` deve essere visibile nella UI, non nascosta solo nel codice. Per questo la stessa regola usata dal calcolatore produce uno stato esplicito: `standard` entro la soglia affidabile, `indicative` quando le ripetizioni equivalenti sono alte ma ancora utilizzabili, `excluded` quando la serie è troppo lunga per contribuire al miglior 1RM stimato.

Il conteggio delle stime è derivato al momento dalle serie filtrate, senza nuova persistenza. Questa scelta mantiene il dato sempre coerente con periodo e tipo sessione selezionati e non vincola il progetto a una formula definitiva.

## Decisione Step 35A - catalogo seed diretto senza nuova UI

Per caricare velocemente l'elenco esteso di attrezzature ed esercizi è stato scelto un catalogo PHP seed, non una nuova interfaccia di import. La priorità è avere subito dati pronti in ambiente demo/sviluppo senza cambiare lo schema database e senza introdurre parsing runtime di file Markdown. Per i database già avviati è stato aggiunto il comando `app:catalog:seed`, che fa upsert per slug e non richiede di svuotare il database.

Gli slug già presenti nei seed storici restano prioritari. Il nuovo catalogo viene aggiunto dopo, ma `AppFixtures` salta gli elementi duplicati in base allo slug. Questa scelta conserva le schede demo esistenti, evita violazioni di unicità e permette di ampliare progressivamente il catalogo senza rompere riferimenti già usati da schede, diario o statistiche.

## Decisione Step 35B - filtri in memoria prima di query builder dedicate

Per il catalogo attuale i filtri vengono applicati in memoria tramite `CatalogListFilter`, partendo dagli elenchi Doctrine già ordinati. La scelta è intenzionale: con circa 90 attrezzature e 100 esercizi non serve ancora introdurre query builder, paginazione server-side o una tabella di categorie separata. Il vantaggio è mantenere la logica testabile, semplice e riutilizzabile anche nel dettaglio scheda.

Se il catalogo crescerà molto oltre questa scala, la stessa semantica dei filtri potrà essere spostata nei repository senza cambiare la UI.

## Decisione Step 35C - La mia palestra come matrice completa del catalogo

`La mia palestra` non deve essere un elenco parziale delle sole attrezzature già presenti nella tabella ponte. Deve invece mostrare tutte le attrezzature del catalogo e usare `GymEquipment.isAvailable` come stato di presenza/non presenza. Questa scelta evita ambiguità dopo import o seed del catalogo: se una nuova attrezzatura entra in `Equipment`, deve comparire nella configurazione palestra.

I collegamenti mancanti vengono creati come presenti di default. È coerente con il flusso già indicato nella UI: l'utente parte dal catalogo completo e disattiva ciò che non esiste nella propria palestra. La sincronizzazione non richiede migration perché sfrutta la relazione esistente tra `GymProfile` ed `Equipment`.

## Decisione Step 35D - disponibilità come filtro, non come blocco rigido

La disponibilità dell'attrezzatura nella palestra deve guidare la selezione degli esercizi, ma non bloccarla in modo irreversibile. Nel dettaglio scheda il select parte filtrato sugli esercizi disponibili, perché è il comportamento più utile nella pratica quotidiana; tuttavia l'utente può visualizzare anche esercizi non disponibili, ad esempio per preparare una scheda destinata a un'altra palestra o per pianificare attrezzature future.

Il filtro è applicato a livello UI e deriva dalla stessa matrice `GymEquipment` usata in `La mia palestra`. Non viene introdotto un vincolo database tra schede ed esercizi disponibili, perché le schede storiche devono restare leggibili anche se una macchina viene disattivata in seguito.
