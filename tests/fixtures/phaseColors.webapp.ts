// Abschrift aus aeroacars-live (Zweig v3, Commit 07f40c8): webapp/src/data/phaseColors.ts
// Nur fuer tests/widget.test.mjs — vergleicht Phasen, Farben und Namen der Karte mit der Webapp.
// Bei Aenderungen in der Webapp diese Datei neu abschreiben und den Test laufen lassen.
// Zentrale Phase-Farbe + Display-Helpers. Wird von LiveMap, FlightBoard,
// PirepFeed, Touchdowns, PilotHistory, Stats, Heatmap genutzt — gleiche Farbe
// überall, gleiche Reihenfolge. Identisch zu --phase-* CSS-Variablen.

import type { Phase } from "../types";

export const PHASE_COLOR: Record<string, string> = {
  PREFLIGHT:        "#94a3b8",  // slate-400
  BOARDING:         "#cbd5e1",  // slate-300 (heller, „Vorbereitung")
  PUSHBACK:         "#14b8a6",  // teal-500
  TAXI_OUT:         "#fbbf24",  // amber-400
  TAKEOFF_ROLL:     "#fb923c",  // orange-400 (zwischen Taxi-Gelb und Takeoff-Orange)
  TAKEOFF:          "#f97316",  // orange-500
  CLIMB:            "#22d3ee",  // cyan-400
  CRUISE:           "#10b981",  // emerald-500
  LEVEL:            "#38bdf8",  // sky-400 (#phase-v2: Level-Off an ATC-Restriktion; identisch zum Client-Badge)
  HOLDING:          "#c084fc",  // purple-400
  DESCENT:          "#3b82f6",  // blue-500
  APPROACH:         "#f59e0b",  // amber-500
  FINAL:            "#ef4444",  // red-500
  LANDING:          "#dc2626",  // red-600
  TAXI_IN:          "#94a3b8",  // slate-400
  BLOCKS_ON:        "#64748b",  // slate-500 (v0.5.18+ canonical)
  ON_BLOCK:         "#64748b",  // legacy alias — alte Daten + Pre-v0.5.18 Clients
  ARRIVED:          "#475569",  // slate-600 (dunkler)
  PIREP_SUBMITTED:  "#334155",  // slate-700 (am dunkelsten, „erledigt")
};

export const PHASE_LIST: Phase[] = [
  "PREFLIGHT", "BOARDING", "PUSHBACK", "TAXI_OUT", "TAKEOFF_ROLL", "TAKEOFF",
  "CLIMB", "CRUISE", "HOLDING", "DESCENT", "APPROACH", "FINAL", "LANDING",
  "TAXI_IN", "BLOCKS_ON", "ARRIVED", "PIREP_SUBMITTED",
];

export function phaseColor(phase: string | null | undefined): string {
  return PHASE_COLOR[phase ?? ""] ?? "#94a3b8";
}

// Sauber formatierte Phase-Labels für UI. Aus dem Pilot-FSM kommen
// Phasen als UPPER_SNAKE_CASE — die in der UI ungefiltert anzuzeigen
// (z.B. "TAKEOFF_ROLL") liest sich häßlich. Hier zentral mappen damit
// LiveMap, FlightBoard, Filter-Dropdowns etc. konsistent bleiben.
const PHASE_LABEL: Record<string, string> = {
  PREFLIGHT:        "Preflight",
  BOARDING:         "Boarding",
  PUSHBACK:         "Pushback",
  TAXI_OUT:         "Taxi Out",
  TAKEOFF_ROLL:     "T/O Roll",
  TAKEOFF:          "Takeoff",
  CLIMB:            "Climb",
  CRUISE:           "Cruise",
  LEVEL:            "Level",
  HOLDING:          "Holding",
  DESCENT:          "Descent",
  APPROACH:         "Approach",
  FINAL:            "Final",
  LANDING:          "Landing",
  TAXI_IN:          "Taxi In",
  BLOCKS_ON:        "Blocks On",
  ON_BLOCK:         "Blocks On",
  ARRIVED:          "Arrived",
  PIREP_SUBMITTED:  "Pirep eingereicht",
};

export function phaseLabel(phase: string | null | undefined): string {
  if (!phase) return "—";
  // Fallback: unbekannte Phase wenigstens entskalieren
  return PHASE_LABEL[phase] ?? phase.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

// #phase-v2: die ANZUZEIGENDE Phase — spiegelt exakt die Client-Logik
// (`phaseBadgeDisplay` in ActiveFlightPanel.tsx). Bei einem Level-Off an einer
// ATC-Höhen-Restriktion trägt `shadow_segment` (aus dem Live-Position-Stream,
// lowercase "level") den Wert; dann zeigt das Badge „Level" — aber NUR im
// Steig-/Sinkflug. Der Reiseflug (CRUISE) klassifiziert kinematisch ebenfalls
// "level", bleibt aber „Cruise" (sonst wäre der ganze Reiseflug „Level").
// `current_phase` kommt UPPER_SNAKE aus dem Wire. Nur für die ANZEIGE
// (Label/Farbe) verwenden — NICHT für Filter/Sort/Zoom (dort roh current_phase).
export function phaseForDisplay(
  currentPhase: string | null | undefined,
  shadowSegment: string | null | undefined,
): string {
  const p = (currentPhase ?? "").toUpperCase();
  const inRestrictable = p === "CLIMB" || p === "DESCENT";
  if (inRestrictable && (shadowSegment ?? "").toLowerCase() === "level") {
    return "LEVEL";
  }
  return currentPhase ?? "—";
}

// #phase-v2 Cutover: `normalizePhaseWire` + `shadowPhaseDiffers` entfernt —
// die dienten nur dem gedimmten „v2:"-Schatten-Badge, das nach dem Cutover weg
// ist (die Hauptphase IST jetzt v2, s. `phaseForDisplay`).

// Ampel der GESAMTNOTE 0..100 — dieselben Stufen wie Notenbuchstabe und
// Notenwort des Clients: ab 75 gut (A+ … B, „Hervorragend"/„Gut"), ab 50
// mittel (C/D, „Ausreichend"), darunter schlecht (F). QS 05.10.2026: vorher
// 75/45 wie die Achsen — 45–49 Punkte waren dann hier gelb, in der
// Kategorie-Pille und im Client (F) aber rot.
export function scoreTone(score: number | null | undefined): "good" | "warn" | "err" | null {
  if (score == null) return null;
  if (score >= 75) return "good";
  if (score >= 50) return "warn";
  return "err";
}
