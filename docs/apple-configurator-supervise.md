# Supervise + non-removable MDM with Apple Configurator

Apple rejects Safari/Configurator installs of MDM profiles with `PayloadRemovalDisallowed=true`
(“A profile containing an MDM payload must be removable”). That flag is only valid for **ADE / Apple Business Manager** enrollment. Manual enroll profiles are always removable; supervision still enables stronger MDM commands.

## 1. Get a fresh profile

On the Mac (or from the console **Enrollment** page), download:

```text
https://mdm.wayda.co.tz/mdm/enroll/<token>
```

Save as `MDM-Enrollment.mobileconfig`.

Or create one in the UI: Enrollment → Create Configurator enrollment → open the enrollment URL and save the file.

**Do not** add this file in Configurator Prepare/Blueprints (often “profile is invalid”). Supervise first, then install via Safari.

## 2. Apple Configurator (USB)

1. Connect the iPhone/iPad with a cable. Unlock it and tap **Trust** if asked.
2. Open **Apple Configurator**.
3. Select the device → **Prepare** (or All Actions → Prepare).
4. Configuration: **Manual**.
5. Server: **Do not enroll in MDM** (you will add our profile instead),  
   **or** add MDM server `https://mdm.wayda.co.tz/mdm` if you prefer Configurator’s MDM field — profile method below is clearer.
6. Enable **Supervise devices**.
7. Organization: create/select your org (needed for supervision identity).
8. **Important:** if the device is already set up, Configurator will **erase** it to supervise. Back up first.
9. Do **not** add the MDM `.mobileconfig` under Add Profiles.
10. Run Prepare; finish Setup Assistant to Home Screen.
11. On the phone open Safari → enrollment URL → Install → Trust.

After setup, the device is supervised and MDM is installed. Profile removal may still be allowed until you use ADE.

## 3. Verify in the console

1. https://mdm.wayda.co.tz → Devices  
2. Device should show enrolled / supervised  
3. Use **Remote control** (Lock / Lost Mode / Erase)

## Notes

- Non-removable MDM requires **Apple Business Manager + ADE**, not Configurator + Safari.
- If Prepare fails on SCEP/MDM, confirm the Mac and phone can reach `https://mdm.wayda.co.tz`.
