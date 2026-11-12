const kuukausiVuosiEl = document.getElementById("kuukausiVuosi");
const paivatRuudukko = document.getElementById("paivatRuudukko");
const valittuInfo = document.getElementById("valittuInfo");

let nykyinenPvm = new Date();
let naytettavaVuosi = nykyinenPvm.getFullYear();
let naytettavaKuukausi = nykyinenPvm.getMonth();

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

    // Korostetaan kuluva päivä
    if (
      paiva === nykyinenPvm.getDate() &&
      kuukausi === nykyinenPvm.getMonth() &&
      vuosi === nykyinenPvm.getFullYear()
    ) {
      paivaRuutu.classList.add("tanaan");
    }

    // Klikkaustapahtuma päivän valintaan
    paivaRuutu.addEventListener("click", () => {
      document.querySelectorAll(".paivat div").forEach(el => el.classList.remove("valittu"));
      paivaRuutu.classList.add("valittu");
      valittuInfo.textContent = `Valittu päivä: ${paiva}.${kuukausi + 1}.${vuosi}`;
    });

    paivatRuudukko.appendChild(paivaRuutu);
  }
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

// Alustetaan kalenteri sivun latautuessa
piirraKalenteri(naytettavaVuosi, naytettavaKuukausi);
