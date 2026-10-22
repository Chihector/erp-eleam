export default async function run(page, ui) {
  await ui.click('@e19'); // Crear Solicitud de Vacaciones
  await page.waitForTimeout(2000);
  return await ui.snapshot();
}