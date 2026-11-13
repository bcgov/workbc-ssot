--
-- Grant additional permissions to special users.
--
GRANT SELECT,INSERT,UPDATE ON TABLE public.sources TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE ON TABLE public.monthly_labour_market_updates TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.industry_outlook TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_industry TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_regional_employment TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_regional_industry_region TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_regional_industry_province TO ssot_lmmu;
