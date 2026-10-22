export default async function run(page, ui) {
  await ui.click('@e19'); // Crear Ingreso
  await page.waitForTimeout(2000);
  return await ui.snapshot();
}