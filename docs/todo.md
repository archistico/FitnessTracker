# Todo

## Breve termine

- Verificare migration SQLite sul PC di sviluppo.
- Eseguire fixture iniziali.
- Aggiungere pagine lista/dettaglio per attrezzature.
- Aggiungere pagina per gestire attrezzature disponibili nella palestra.
- Aggiungere pagine lista/dettaglio per esercizi.
- Aggiungere filtro esercizi disponibili.

## Medio termine

- Implementare schede allenamento.
- Implementare diario sessioni reali.
- Supportare esercizi cardio, tempo, reps-only e corpo libero.
- Implementare calibrazione iniziale come sessione dedicata.
- Implementare profilo carichi per esercizio.

## Lungo termine

- Implementare Trenino Invictus.
- Implementare correzione carichi intra-sessione.
- Implementare gestione sedute saltate.
- Implementare statistiche.
- Implementare zona consigli spiegabili.

## Punti da migliorare

- La lista fixture esercizi è volutamente iniziale e non completa: andrà ampliata con tutti gli esercizi indicati.
- Le immagini sono predisposte tramite `imagePath`, ma l'upload non è ancora implementato.
- I muscoli sono salvati come JSON semplice; se serviranno filtri avanzati, si potrà normalizzare con tabelle dedicate.

## Todo aggiunti dopo Step 2

- Aggiungere CRUD controllato per schede di allenamento.
- Decidere se la configurazione disponibilità attrezzatura deve supportare più palestre o solo una palestra principale nella MVP.
- Aggiungere in futuro immagini esercizio/attrezzatura; per ora il database ha già `imagePath`, ma non c'è upload.
- Rendere più ricco il catalogo esercizi con tutte le voci della lista iniziale utente.
- Valutare una pagina “esercizi non disponibili” con suggerimenti di sostituzione.

## Todo aggiunti dopo Step 3

- Aggiungere modifica di una scheda esistente.
- Aggiungere riordino manuale degli esercizi dentro una scheda.
- Filtrare gli esercizi aggiungibili alla scheda in base alle attrezzature disponibili nella palestra.
- Aggiungere duplicazione scheda, utile per creare varianti settimanali.
- Creare `WorkoutSession`, `ExerciseSession` e `SetLog` per il diario reale.
- Avviare una sessione reale partendo da una scheda.
- Trasformare gli esercizi pianificati in serie reali compilabili.

## Todo aggiunti dopo Step 4

- Aggiungere sessione libera non basata su una scheda.
- Aggiungere marcatura esplicita di esercizio saltato e serie saltata.
- Migliorare la UI di registrazione serie separando meglio esercizi peso/reps, cardio, tempo e reps-only.
- Aggiungere riepilogo sessione con volume, tonnellaggio e ripetizioni in riserva medie.
- Aggiungere modifica/cancellazione sessione.
- Usare il diario reale come base della calibrazione iniziale.
- Preparare il primo servizio di analisi carichi, inizialmente solo descrittivo.

## Todo aggiunti dopo Step 5

- Aggiungere sessione di calibrazione iniziale basata su `WorkoutSessionType::Calibration`.
- Creare servizio di stima carichi da peso/reps/RIR.
- Creare profilo carichi per esercizio con range 10-12, 8-10, 6-8, 4-6.
- Migliorare UI del dettaglio sessione mostrando campi diversi per tracking mode.
- Aggiungere possibilità di riaprire una sessione chiusa.
- Aggiungere sessione libera non legata a scheda.

## Todo aggiunti dopo Step 6

- Valutare pulsanti rapidi per RIR e Carico, più comodi dei select su smartphone.
- Aggiungere timer recupero integrato nella serie.
- Valutare auto-scroll alla serie successiva dopo il salvataggio.
- Valutare input più specializzati per durata, ad esempio minuti/secondi invece di soli secondi.
- Mantenere la UI mobile-first anche per calibrazione iniziale e Trenino.

## Todo aggiunti dopo Step 6B

- Implementare calibrazione iniziale per creare i primi `targetWeightKg` reali.
- Implementare profilo carichi per spiegare da dove arriva ogni peso suggerito.
- Quando il profilo carichi esiste, precompilare o suggerire il campo `Kg` della serie in modo evidente.
- Valutare pulsanti rapidi per `Carico` e `Sforzo`, perché i select possono essere lenti da smartphone.

## Todo aggiunti dopo Step 7

- Mantenere lo stile `app-mobile-card` come riferimento per calibrazione iniziale e Trenino.
- Valutare in seguito una pulizia delle classi specifiche della pagina sessione, fondendole con quelle globali dove possibile.
- Aggiungere eventualmente micro-componenti Twig riutilizzabili per card, metriche e blocchi informativi quando le pagine cresceranno.
- Verificare da smartphone reale `/`, `/equipment`, `/gym/equipment`, `/exercises`, `/workout-plans`, `/workout-plans/{slug}` e `/workout-sessions`.

## Todo aggiunti dopo Step 8

- Usare `ExerciseLoadProfile` per valorizzare `targetWeightKg` quando si avvia una sessione da scheda.
- Mostrare nella pagina esercizio il profilo carichi più recente.
- Aggiungere una pagina dettaglio profilo carichi con formula, serie usata e confidenza.
- Rendere la calibrazione più guidata, suggerendo il peso della prossima serie test in base alla serie appena registrata.
- Permettere una sessione di calibrazione multi-esercizio.
- Preparare l'integrazione con Trenino Invictus usando i range salvati.


## Aggiornamento UI diario - etichette esplicite

Nel diario allenamenti le etichette dei campi devono evitare abbreviazioni quando lo spazio lo consente. `Rec. sec.` diventa `Recupero effettivo (secondi)`, `Sec.` diventa `Secondi`, `Reps` diventa `Ripetizioni`. Il campo `Livello` è esplicitato come `Livello / resistenza macchina`, da usare per cyclette, vogatore, macchine cardio o attrezzi con livelli numerici. Il campo RIR resta tecnicamente importante, ma viene presentato come `Ripetizioni in riserva (RIR)` con combobox guidata, perché l’utente non deve ricordare il significato dell’acronimo durante l’allenamento.

## Todo aggiunti dopo Step 11

- Salvare o calcolare lo step corrente del Trenino per ogni esercizio.
- Analizzare l'esito della seduta per decidere se avanzare, ripetere o ridurre lo step.
- Applicare correzioni intra-sessione del carico dopo ogni serie completata.
- Gestire il caso di sedute saltate o pausa lunga prima di proporre lo step successivo.
- Mostrare nella scheda una descrizione completa dello step Trenino che verrà generato prima di avviare l'allenamento.

## Todo aggiunti dopo Step 12

- Gestire pausa lunga tra sedute: dopo molti giorni senza quell'esercizio, ripetere o ridurre lo step anche se la seduta precedente era buona.
- Mostrare nella scheda lo step Trenino previsto prima di premere “Avvia allenamento”.
- Aggiungere una pagina stato progressione per esercizio, con storico step e decisioni.
- Raffinare la correzione dei carichi: non solo step successivo, ma anche aumento/mantenimento/riduzione del peso per range.
- Evitare l'avvio accidentale di più sessioni aperte della stessa scheda se esiste già una sessione in corso.

## Todo dopo Step 14

- Usare il peso consigliato dalla serie precedente per precompilare la serie successiva compatibile.
- Separare meglio la logica di correzione per esercizi di isolamento rispetto ai fondamentali.
- Integrare la correzione intra-sessione con la correzione del profilo carichi a fine allenamento.

## Todo dopo Step 15

- Mostrare in UI quando un carico target è stato aggiornato dalla serie precedente, se in futuro servirà storicizzare la motivazione.
- Aggiornare `ExerciseLoadProfile` a fine allenamento usando le serie reali consolidate.
- Differenziare meglio gli incrementi per esercizi con manubri, macchine e bilanciere.

## Dopo Step 16

- Valutare una pulizia dati per eventuali serie storiche con `perceivedLoad = failure`, se necessario.
- Continuare a evitare input duplicati nella pagina diario: ogni concetto deve avere un solo campo di inserimento.

## Todo dopo Step 17

- Valutare se il carico suggerito dalla serie precedente debba essere salvato sempre nel database o rimanere solo suggerimento visuale.
- Aggiungere una micro-interazione per copiare il peso suggerito nel campo `Peso (kg)` della serie.
- Rendere più evidente quando il suggerimento arriva da calibrazione, da Trenino o dalla serie precedente.

## Todo dopo Step 18

- Valutare se aggiungere un pulsante simile anche per il recupero previsto.
- Valutare un'evidenziazione automatica del prossimo campo da compilare dopo la copia del peso.
- Integrare in futuro una modalità “serie rapida” per esercizi molto ripetitivi.

## Todo dopo Step 19

- Valutare timer recupero per serie con avvio rapido.
- Evidenziare le serie ancora aperte e la prossima serie da compilare.
- Integrare una modalità di inserimento rapido per serie identiche.

## Todo dopo Step 20

- Valutare autoscroll controllato alla prossima serie dopo il salvataggio.
- Aggiungere in futuro una modalità “solo prossima serie” per ridurre ulteriormente la densità della pagina su smartphone.
- Valutare badge più specifici per serie completata, saltata, parziale e ancora aperta.

## Todo dopo Step 21

- Valutare una modalità compatta che mostri solo la prossima serie aperta e nasconda le serie già completate.
- Valutare un pulsante `Mostra tutto` / `Solo aperte` per gli allenamenti molto lunghi.
- Aggiungere test funzionali controller quando la suite sarà pronta per test HTTP più completi.

## Todo dopo Step 22

- Valutare una vista ancora più compatta `Prossima serie soltanto`.
- Aggiungere un riepilogo rapido delle serie completate quando la modalità solo aperte è attiva.
- Valutare una preferenza utente persistente lato database, se in futuro verrà introdotto il login reale.

## CRUD da completare

- Attrezzature: aggiungere, modificare, eliminare. Implementato nello Step 23.
- Esercizi: aggiungere, modificare, eliminare, con gestione muscoli, tracking mode, attrezzatura collegata e incremento default.
- Schede: aggiungere, modificare, eliminare, duplicare, ordinare esercizi e modificare prescrizioni.
- Diario: modificare dati sessione, eliminare sessioni, eliminare serie/sessioni di test, gestire note generali.

## CRUD aggiornato dopo Step 24

- Attrezzature: CRUD implementato.
- Esercizi: CRUD implementato.
- Schede: da implementare creazione/modifica/eliminazione, duplicazione, ordinamento esercizi e modifica prescrizioni.
- Diario: da implementare modifica dati sessione, eliminazione sessioni e pulizia sessioni di test.

## CRUD aggiornato dopo Step 25

- Attrezzature: CRUD implementato.
- Esercizi: CRUD implementato.
- Schede: CRUD implementato, incluse righe esercizio e riordino.
- Diario: da implementare modifica dati sessione, eliminazione sessioni e pulizia sessioni di test.
- Miglioramento futuro Schede: duplicazione scheda e copia da scheda esistente.

## CRUD aggiornato dopo Step 26

- Attrezzature: CRUD implementato.
- Esercizi: CRUD implementato.
- Schede: CRUD implementato, incluse righe esercizio e riordino.
- Diario: CRUD base implementato con modifica dati sessione ed eliminazione sessione.
- Miglioramento futuro Diario: modifica rapida dei dati sessione direttamente dal dettaglio e pulizia massiva delle sessioni di test.

## Todo dopo Step 27

- Aggiungere una pagina di confronto o riepilogo dopo duplicazione scheda.
- Valutare una funzione `Duplica e modifica subito` con focus sulle righe esercizio.
- Prossimo blocco consigliato: statistiche/progressione, oppure rifinitura UX dei CRUD appena introdotti.

## Todo dopo Step 28

- Verificare visivamente tutte le card CRUD con 3 o più azioni: Schede, Diario, Esercizi, Attrezzature.
- Valutare se separare azioni principali e azioni distruttive in due righe distinte nelle card più dense.

## Todo dopo Step 29

- Verificare visivamente le pagine CRUD con azioni distruttive: Attrezzature, Esercizi, Schede, Diario.
- Valutare una conferma più elegante tramite modal invece del `confirm()` browser.
- Valutare una distinzione ulteriore tra `Rimuovi dalla scheda` ed `Elimina definitivamente`.

## Todo dopo Step 30

- Valutare testi di conferma più specifici, per esempio indicando il nome dell'elemento eliminato.
- Valutare una seconda conferma per cancellazioni molto distruttive se emergerà la necessità.
- Prossimo blocco consigliato: statistiche/progressione o rifinitura dei form CRUD con validazioni visive.

## Todo dopo Step 31A

- Estendere la conservazione dei valori inseriti quando un form fallisce la validazione.
- Aggiungere messaggi di conferma modale con nome specifico dell'elemento eliminato.
- Valutare validazioni più ricche per righe scheda: coerenza tra serie, reps, durata e progressione.
- Ripassare i CRUD su smartphone dopo l'introduzione degli errori inline.

## Todo dopo Step 31B

- Aggiungere testi di conferma modale con il nome specifico dell'elemento eliminato.
- Rafforzare le validazioni delle righe scheda: combinazioni non coerenti tra progressione, serie, reps, durata e recupero.
- Valutare auto-generazione live dello slug da nome lato JavaScript, solo come aiuto visivo.
- Fare un passaggio manuale su smartphone dei quattro CRUD.

## Todo dopo Step 31C

- Rafforzare le validazioni delle righe scheda: combinazioni non coerenti tra progressione, serie, reps, durata e recupero.
- Valutare auto-generazione live dello slug da nome lato JavaScript.
- Fare un passaggio manuale su smartphone dei quattro CRUD.
- Prossimo blocco funzionale consigliato: statistiche/progressione base.

## Todo dopo Step 31D

- Valutare errori inline anche sulle righe esercizio della scheda, non solo flash e redirect ad anchor.
- Valutare auto-generazione live dello slug da nome lato JavaScript.
- Fare un passaggio manuale su smartphone dei quattro CRUD.
- Prossimo blocco funzionale consigliato: statistiche/progressione base.

## Todo dopo Step 31E

- Fare un passaggio manuale su smartphone dei quattro CRUD.
- Valutare errori inline anche sulle righe esercizio della scheda, non solo flash e redirect ad anchor.
- Prossimo blocco funzionale consigliato: statistiche/progressione base.

## Todo dopo Step 32A

- Aggiungere dettaglio storico per singolo esercizio.
- Aggiungere filtri per periodo e tipo sessione.
- Aggiungere grafici semplici per peso/volume/RIR nel tempo.
- Valutare aggiornamento assistito del profilo carichi dopo gli allenamenti reali.

## Todo dopo Step 32B

- Aggiungere filtri periodo nella pagina Statistiche e nello storico esercizio.
- Aggiungere grafici semplici peso/volume/RIR per esercizio.
- Valutare aggiornamento assistito del profilo carichi partendo dagli ultimi dati reali.
- Valutare timer recupero nella pagina diario.

## Todo dopo Step 32C

- Aggiungere grafici semplici peso/volume/RIR per esercizio.
- Valutare aggregazioni mensili o settimanali.
- Valutare aggiornamento assistito del profilo carichi partendo dagli ultimi dati reali.
- Valutare timer recupero nella pagina diario.

## Todo dopo Step 32D

- Valutare un trend su finestre temporali più lunghe, non solo sulle ultime 8 sessioni.
- Aggiungere una metrica di miglior set stimato, utile per confrontare peso e ripetizioni anche quando i range cambiano.
- Collegare le statistiche al futuro aggiornamento assistito del profilo carichi dell'esercizio.
- Valutare grafici interattivi solo quando le statistiche richiederanno zoom, tooltip avanzati o confronto multi-esercizio.
