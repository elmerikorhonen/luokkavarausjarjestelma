const kuukausiVuosiEl = document.getElementById("kuukausiVuosi");
const paivatRuudukko = document.getElementById("paivatRuudukko");
const valittuInfo = document.getElementById("valittuInfo");
const paivamaaraKentta = document.getElementById("paivamaara");
const paivamaaraNakyma = document.getElementById("paivamaara_nakyma");
const varausLomake = document.querySelector('form[action="huoneet.php"]');

const nykyinenPvm = new Date();
let valittuPaiva = null;
if (/^\d{4}-\d{2}-\d{2}$/.test(paivamaaraKentta.value)) {
  const [vuosi, kuukausi, paiva] = paivamaaraKentta.value.split("-").map(Number);
  valittuPaiva = new Date(vuosi, kuukausi - 1, paiva);
}
let naytettavaVuosi = valittuPaiva?.getFullYear() ?? nykyinenPvm.getFullYear();
let naytettavaKuukausi = valittuPaiva?.getMonth() ?? nykyinenPvm.getMonth();

const kuukaudet = [
  "Tammikuu", "Helmikuu", "Maaliskuu", "Huhtikuu",
  "Toukokuu", "Kesäkuu", "Heinäkuu", "Elokuu",
  "Syyskuu", "Lokakuu", "Marraskuu", "Joulukuu"
];

function piirraKalenteri(vuosi, kuukausi) {
  paivatRuudukko.innerHTML = "";
  kuukausiVuosiEl.textContent = `${kuukaudet[kuukausi]} ${vuosi}`;

  // Kuukauden 1. päivän viikonpäivä (0 = Su, 1 = Ma ... 6 = La)
  const ensimmainenPaiva = new Date(vuosi, kuukausi, 1).getDay();
  // Muutetaan suomalaiseen muotoon (0 = Ma ... 6 = Su)
  const tyhjiaAlussa = ensimmainenPaiva === 0 ? 6 : ensimmainenPaiva - 1;
  
  // Kuukauden päivien määrä
  const paiviaKuukaudessa = new Date(vuosi, kuukausi + 1, 0).getDate();

  // Lisätään tyhjät ruudut ennen 1. päivää
  for (let i = 0; i < tyhjiaAlussa; i++) {
    const tyhjaRuutu = document.createElement("div");
    tyhjaRuutu.classList.add("tyhja");
    paivatRuudukko.appendChild(tyhjaRuutu);
  }

  // Lisätään kuukauden päivät
  for (let paiva = 1; paiva <= paiviaKuukaudessa; paiva++) {
    const paivaRuutu = document.createElement("div");
    paivaRuutu.textContent = paiva;
    paivaRuutu.setAttribute("role", "button");
    paivaRuutu.setAttribute("tabindex", "0");
    paivaRuutu.setAttribute("aria-label", `${paiva}. ${kuukaudet[kuukausi]}ta ${vuosi}`);

    // Korostetaan kuluva päivä
    if (
      paiva === nykyinenPvm.getDate() &&
      kuukausi === nykyinenPvm.getMonth() &&
      vuosi === nykyinenPvm.getFullYear()
    ) {
      paivaRuutu.classList.add("tanaan");
    }

    if (valittuPaiva
      && paiva === valittuPaiva.getDate()
      && kuukausi === valittuPaiva.getMonth()
      && vuosi === valittuPaiva.getFullYear()) {
      paivaRuutu.classList.add("valittu");
      paivaRuutu.setAttribute("aria-pressed", "true");
    } else {
      paivaRuutu.setAttribute("aria-pressed", "false");
    }

    // Klikkaustapahtuma päivän valintaan
    paivaRuutu.addEventListener("click", () => {
      valitsePaiva(vuosi, kuukausi, paiva);
    });
    paivaRuutu.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        valitsePaiva(vuosi, kuukausi, paiva);
      }
    });

    paivatRuudukko.appendChild(paivaRuutu);
  }
}

function valitsePaiva(vuosi, kuukausi, paiva) {
  valittuPaiva = new Date(vuosi, kuukausi, paiva);
  const kuukausiNumero = String(kuukausi + 1).padStart(2, "0");
  const paivaNumero = String(paiva).padStart(2, "0");
  paivamaaraKentta.value = `${vuosi}-${kuukausiNumero}-${paivaNumero}`;
  paivamaaraNakyma.value = `${paivaNumero}.${kuukausiNumero}.${vuosi}`;
  valittuInfo.textContent = `Valittu päivä: ${paivaNumero}.${kuukausiNumero}.${vuosi}`;
  piirraKalenteri(vuosi, kuukausi);
}

document.getElementById("edellinen").addEventListener("click", () => {
  naytettavaKuukausi--;
  if (naytettavaKuukausi < 0) {
    naytettavaKuukausi = 11;
    naytettavaVuosi--;
  }
  piirraKalenteri(naytettavaVuosi, naytettavaKuukausi);
});

document.getElementById("seuraava").addEventListener("click", () => {
  naytettavaKuukausi++;
  if (naytettavaKuukausi > 11) {
    naytettavaKuukausi = 0;
    naytettavaVuosi++;
  }
  piirraKalenteri(naytettavaVuosi, naytettavaKuukausi);
});

if (valittuPaiva) {
  const paivaNumero = String(valittuPaiva.getDate()).padStart(2, "0");
  const kuukausiNumero = String(valittuPaiva.getMonth() + 1).padStart(2, "0");
  paivamaaraNakyma.value = `${paivaNumero}.${kuukausiNumero}.${valittuPaiva.getFullYear()}`;
  valittuInfo.textContent = `Valittu päivä: ${paivaNumero}.${kuukausiNumero}.${valittuPaiva.getFullYear()}`;
}

varausLomake.addEventListener("submit", (event) => {
  if (!paivamaaraKentta.value) {
    event.preventDefault();
    valittuInfo.textContent = "Valitse ensin varauspäivä kalenterista.";
    paivamaaraNakyma.focus();
  }
});

// Alustetaan kalenteri sivun latautuessa
piirraKalenteri(naytettavaVuosi, naytettavaKuukausi);
