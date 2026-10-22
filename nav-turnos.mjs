export default async function run(page, ui) {
  await ui.click('@e6'); // Turnos link
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}