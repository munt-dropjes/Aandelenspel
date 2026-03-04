<template>
  <div class="container mb-5">
    <div class="row justify-content-center">
      <div class="col-lg-10">

        <div class="text-center mb-4 pt-3">
          <p class="lead text-muted">Spelregels & Handleiding voor Patrouilles</p>
        </div>

        <div class="card shadow-sm mb-4 border-0">
          <div class="card-body fs-5">
            Welkom bij het Aandelenspel! In deze game strijden
            <span v-if="playerCompanies.length > 0">
                            de patrouilles (<span v-for="(c, index) in playerCompanies" :key="c.id">
                                <strong :style="{ color: c.color }">{{ c.name }}</strong><span v-if="index < playerCompanies.length - 2">, </span><span v-else-if="index === playerCompanies.length - 2"> en </span>
                            </span>)
                        </span>
            <span v-else>verschillende patrouilles</span>
            als bedrijven tegen elkaar

            <span v-if="hasNpc">
                            én tegen <strong :style="{ color: mainNpc.color }">{{ mainNpc.name }}</strong>
                        </span>

            om het machtigste en rijkste bedrijf van het spel te worden.
            <br><br>
            <strong>Het doel van het spel is simpel:</strong> zorg dat jouw patrouille aan het eind van het weekend de hoogste <strong class="text-success">Totale Netto Waarde</strong> heeft. Dit doe je door taken succesvol (en zo snel mogelijk!) uit te voeren, slim te handelen in aandelen, en strategische deals te sluiten.
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-display me-2"></i>1. Het Dashboard & Jouw Portfolio</h5>
          </div>
          <div class="card-body">
            <h6 class="fw-bold text-primary">Begrippenlijst</h6>
            <dl class="row mb-4">
              <dt class="col-sm-3 text-success">Cash (ƒ)</dt>
              <dd class="col-sm-9">
                Het liquide geld dat jouw bedrijf momenteel op de bank heeft staan. Hiermee kun je aandelen kopen of rekeningen betalen.
                <div class="alert alert-warning py-2 mt-2 mb-0 small">
                  <i class="bi bi-eye-slash-fill me-1"></i> <strong>Fog of War:</strong> Je ziet alleen je eigen Cash. Het saldo van andere groepen is streng geheim!
                </div>
              </dd>

              <dt class="col-sm-3 text-info mt-3">Koerswaarde</dt>
              <dd class="col-sm-9 mt-3">Wat 1 aandeel van een groep op dit moment waard is. Dit is voor iedereen openbaar.</dd>

              <dt class="col-sm-3 text-primary mt-3">Netto Waarde</dt>
              <dd class="col-sm-9 mt-3">Jouw Cash + de totale waarde van alle aandelen die je bezit. <strong>Dit is het getal dat bepaalt wie er wint!</strong></dd>
            </dl>

            <h6 class="fw-bold text-primary border-top pt-3">De Aandelenverdeling (Portfolio)</h6>
            <p>In de tabel zie je precies wie wat bezit. Elke groep begint standaard met:</p>
            <ul>
              <li><strong>25 aandelen</strong> van de eigen familie/patrouille.</li>
              <li><strong>5 aandelen</strong> van elke andere patrouille.</li>
              <li v-if="hasNpc"><strong>5 aandelen</strong> van {{ mainNpc.name }}.</li>
            </ul>
            <p class="text-muted fst-italic">
              <i class="bi bi-lightbulb-fill text-warning"></i> Tip: Kijk goed naar de "Eigen" aandelen. Als je veel aandelen hebt van een groep die goed presteert <span v-if="firstPlayer">(zoals <strong :style="{color: firstPlayer.color}">{{ firstPlayer.name }}</strong>)</span>, stijgt jouw eigen Netto Waarde automatisch mee!
            </p>
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>2. De Live Koers & Het Algoritme</h5>
          </div>
          <div class="card-body">
            <p>De grafiek updatet live en laat precies zien wie er aan het winnen is. De aandelenkoers van een patrouille wordt berekend op basis van <strong>alles wat zij bezitten</strong> (Cash + Portfolio).</p>

            <ol>
              <li><strong>Waarde van je portfolio:</strong> De waarde van de aandelen die je bezit wordt berekend op basis van de <em>Cash</em> van die bedrijven (Cash / 100).</li>
              <li><strong>Jouw eigen koers:</strong> De bank berekent jouw Totale Netto Waarde (Jouw Cash + Waarde portfolio). Jouw aandelenkoers is 1% van dat totaal.</li>
            </ol>

            <div class="alert alert-secondary text-center fs-5 font-monospace mb-3">
              Koers = Totale Netto Waarde / 100
            </div>

            <p class="mb-0 text-muted small" v-if="firstPlayer">
              <em>Rekenvoorbeeld:</em> Als <strong>{{ firstPlayer.name }}</strong> ƒ 200.000 Cash hebben, én een aandelenportfolio t.w.v. ƒ 50.000, is hun Totale Netto Waarde ƒ 250.000. Ieder aandeel '{{ firstPlayer.name }}' is op de beurs dan <strong>ƒ 2.500</strong> waard.
            </p>
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>3. Geld Verdienen (Het Taaksysteem)</h5>
          </div>
          <div class="card-body">
            <p>Iedere bedrijf mag zelf beslissen welke opdrachten ze doen en wanneer. Maar let op: <strong>Snelheid is cruciaal!</strong> Elke patrouille heeft <strong>slechts 1 poging</strong> per opdracht.</p>

            <ul class="mb-3">
              <li><span class="badge bg-warning text-dark">1e groep</span> Krijgt de hoofdprijs.</li>
              <li><span class="badge bg-secondary">2e groep</span> Krijgt de helft van de hoofdprijs.</li>
              <li><span class="badge bg-danger">3e groep</span> Krijgt nog een kleine vergoeding.</li>
              <li><span class="badge bg-dark">4e en 5e groep</span> Jullie zijn te laat. Het kost je direct geld!</li>
              <li><span class="text-danger fw-bold"><i class="bi bi-x-circle-fill"></i> Mislukt</span> Als je een taak fout doet of het opgeeft, krijg je direct de harde Boete (Penalty) afgeschreven.</li>
            </ul>

            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle text-center small">
                <thead class="table-light">
                <tr>
                  <th class="text-start">Categorie</th>
                  <th>1e Plaats</th>
                  <th>2e Plaats</th>
                  <th>3e Plaats</th>
                  <th>4e/5e Plaats</th>
                  <th class="text-danger">Mislukt (Boete)</th>
                </tr>
                </thead>
                <tbody>
                <tr><td class="text-start fw-bold">Vragen</td><td>ƒ 5.000</td><td>ƒ 2.500</td><td>ƒ 1.000</td><td>-ƒ 2.500 / -ƒ 5.000</td><td class="text-danger fw-bold">-ƒ 5.000</td></tr>
                <tr><td class="text-start fw-bold">3e Klasse</td><td>ƒ 25.000</td><td>ƒ 12.500</td><td>ƒ 5.000</td><td>-ƒ 12.500 / -ƒ 25.000</td><td class="text-danger fw-bold">-ƒ 25.000</td></tr>
                <tr><td class="text-start fw-bold">2e Klasse / Overige</td><td>ƒ 50.000</td><td>ƒ 25.000</td><td>ƒ 10.000</td><td>-ƒ 25.000 / -ƒ 50.000</td><td class="text-danger fw-bold">-ƒ 50.000</td></tr>
                <tr><td class="text-start fw-bold">1e Klasse</td><td>ƒ 100.000</td><td>ƒ 50.000</td><td>ƒ 20.000</td><td>-ƒ 50.000 / -ƒ 100.000</td><td class="text-danger fw-bold">-ƒ 100.000</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-send-fill me-2"></i>4. Geld Overmaken</h5>
              </div>
              <div class="card-body">
                <p>Samenwerken of elkaar afpersen? Via <router-link to="/transacties">Mijn Rekening</router-link> zie je jouw geheime bankafschrift en kun je direct Cash sturen naar een andere patrouille.</p>
                <p class="text-muted small">Let op: Je kunt alleen geld uitgeven dat je daadwerkelijk hebt!</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-briefcase-fill me-2"></i>5. Aandelen Handelen</h5>
              </div>
              <div class="card-body">
                <ul>
                  <li><strong>Kopen van de Bank:</strong> Je betaalt de huidige marktprijs. Direct verwerkt.</li>
                  <li><strong>Trade Offers:</strong> Stuur een bod naar een andere groep om over de prijs te onderhandelen. Als zij accepteren en beide partijen hebben voldoende middelen, is de deal rond!</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div v-if="hasNpc" class="card shadow-sm mb-4" :style="{ border: `2px solid ${mainNpc.color}` }">
          <div class="card-header text-white" :style="{ backgroundColor: mainNpc.color }">
            <h5 class="mb-0"><i class="bi bi-incognito me-2"></i>6. {{ mainNpc.name }} (De Eindbaas & Gier)</h5>
          </div>
          <div class="card-body">
            <p><strong>{{ mainNpc.name }}</strong> doet ook mee op de beurs als een speciale computergestuurde speler.</p>
            <ul>
              <li><strong>De Benchmark (RNG):</strong> {{ mainNpc.name }} doet geen taken. Elke minuut rollen zij virtuele dobbelstenen voor "Subsidie" of "Materiaalkosten".</li>
              <li><strong>De Aasgier:</strong> Faalt jouw patrouille een taak? Dan ruikt {{ mainNpc.name }} bloed. Ze kunnen je automatisch een meedogenloos <strong>Trade Offer</strong> sturen om jouw aandelen op te kopen met 30% korting terwijl je kwetsbaar bent. Accepteer je de slechte deal voor snel geld, of weiger je?</li>
            </ul>
          </div>
        </div>

        <div class="alert alert-success shadow-sm">
          <h4 class="alert-heading fw-bold"><i class="bi bi-trophy-fill me-2"></i>Hoe win je het spel?</h4>
          <hr>
          <ol class="mb-0 fs-5">
            <li><strong>Snelheid:</strong> Doe je taken als eerste. Te laat zijn of fouten maken wordt keihard afgestraft.</li>
            <li><strong>Investeren:</strong> Koop vroeg in het spel aandelen van patrouilles <span v-if="hasNpc">(of {{ mainNpc.name }}!)</span> waarvan je denkt dat ze veel gaan verdienen.</li>
            <li><strong>Onderhandelen:</strong> Wees niet bang om deals te sluiten via de Trade Offers.</li>
            <li><strong>Eindafrekening:</strong> Zorg dat je aan het eind van het kamp de allerhoogste <strong>Totale Netto Waarde</strong> hebt!</li>
          </ol>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { inject, computed } from 'vue';

const companies = inject('companies');

// Filter companies into players and NPCs
const playerCompanies = computed(() => {
  return companies ? companies.filter(c => !c.is_npc) : [];
});

const npcCompanies = computed(() => {
  return companies ? companies.filter(c => c.is_npc) : [];
});

// Helpers for dynamic rendering
const hasNpc = computed(() => npcCompanies.value.length > 0);
const mainNpc = computed(() => hasNpc.value ? npcCompanies.value[0] : null);

const firstPlayer = computed(() => playerCompanies.value.length > 0 ? playerCompanies.value[0] : null);
</script>