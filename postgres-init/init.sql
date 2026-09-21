CREATE ROLE ssot_readonly NOLOGIN;
GRANT CONNECT ON DATABASE ssot TO ssot_readonly;
GRANT USAGE ON SCHEMA public TO ssot_readonly;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO ssot_readonly;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO ssot_readonly;

CREATE ROLE ssot_lmmu LOGIN PASSWORD 'ssot_lmmu';
GRANT CONNECT ON DATABASE ssot TO ssot_lmmu;
GRANT USAGE ON SCHEMA public TO ssot_lmmu;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO ssot_lmmu;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO ssot_lmmu;

-- https://postgrest.org/en/stable/schema_cache.html#schema-reloading
-- Create an event trigger function
CREATE OR REPLACE FUNCTION public.pgrst_watch()
RETURNS event_trigger
LANGUAGE plpgsql
AS $$
BEGIN
  NOTIFY pgrst, 'reload schema';
END;
$$;

-- Data normalization functions
CREATE OR REPLACE FUNCTION normalize_region_name(
  p_region text
)
RETURNS text
LANGUAGE plpgsql
AS $$
BEGIN
    CASE p_region
        WHEN 'British Columbia' THEN RETURN 'british_columbia';
        WHEN 'Cariboo' THEN RETURN 'cariboo';
        WHEN 'Kootenay' THEN RETURN 'kootenay';
        WHEN 'Northeast' THEN RETURN 'northeast';
        WHEN 'Mainland/Southwest' THEN RETURN 'mainland_southwest';
		WHEN 'Lower Mainland Southwest' THEN RETURN 'mainland_southwest';
        WHEN 'Thompson-Okanagan' THEN RETURN 'thompson_okanagan';
        WHEN 'Thompson Okanagan' THEN RETURN 'thompson_okanagan';
        WHEN 'Vancouver Island/Coast' THEN RETURN 'vancouver_island_coast';
        WHEN 'Vancouver Island And Coast' THEN RETURN 'vancouver_island_coast';
        WHEN 'North Coast and Nechako' THEN RETURN 'north_coast_nechako';
        WHEN 'North Coast And Nechako' THEN RETURN 'north_coast_nechako';
        ELSE RAISE EXCEPTION 'Unknown region: %', p_region;
    END CASE;
END;
$$;

CREATE OR REPLACE FUNCTION normalize_industry_name(
	p_industry text,
	p_exceptions text[] DEFAULT '{}'
)
RETURNS text
LANGUAGE plpgsql
AS $$
DECLARE
    v_key text;
	v_exc text;
BEGIN
    FOREACH v_exc IN ARRAY p_exceptions LOOP
        IF p_industry IS NOT DISTINCT FROM v_exc THEN
            RETURN p_industry;
        END IF;
    END LOOP;

    SELECT i.key INTO v_key
    FROM industries i
    WHERE regexp_replace(i.name, 'with|and|,|\s*', '', 'gi') ILIKE
          regexp_replace(p_industry, 'with|and|,|\s*', '', 'gi');

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Unknown industry: %', p_industry;
    END IF;

    RETURN v_key;
END;
$$;

-- Assertion functions for data migration purposes
CREATE OR REPLACE FUNCTION public.assert_empty(
    p_query text,
    p_params text[] DEFAULT '{}',
    p_message text DEFAULT 'Assertion failed: Found matching rows'
)
RETURNS void
LANGUAGE plpgsql
AS $$
DECLARE
    has_rows boolean;
BEGIN
    EXECUTE format('SELECT EXISTS(%s)', p_query)
		INTO has_rows
		USING p_params;
    IF has_rows THEN
        RAISE EXCEPTION '%', p_message;
    END IF;
END;
$$;

-- This event trigger will fire after every ddl_command_end event
CREATE EVENT TRIGGER pgrst_watch
  ON ddl_command_end
  EXECUTE PROCEDURE public.pgrst_watch();

-- Turn on case-insensitive text extension
CREATE EXTENSION citext;
